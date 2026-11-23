<?php
/**
 * Page content: the words and photos of the website's cake, bakery, gallery, award
 * and policy pages (data/pages.json), and the testimonials (data/testimonials.json).
 * The home, about and contact pages are part of the design and aren't edited here.
 *
 *   ?slug=wedding-cakes     edit one page
 *   ?view=testimonials      edit the testimonials
 */
if (!defined('ADMIN')) { http_response_code(403); exit('Forbidden'); }

const PAGE_TYPES = [
    'occasion' => ['Cakes for every occasion', ''],
    'bakery'   => ['From our counter', ''],
    'gallery'  => ['Gallery', ''],
    'award'    => ['Awards', 'awards/'],
    'policy'   => ['Policies', ''],
];
const UPLOADS_DIR = __DIR__ . '/../../../images/uploads';

function page_url(string $slug, array $p): string
{
    return SITE_BASE . PAGE_TYPES[$p['type']][1] . $slug . '/';
}

/** pages.json body blocks → text for the editor: paragraphs split by a blank line, list items start with "- ". */
function body_to_text(array $body): string
{
    $out = [];
    $prevLi = false;
    foreach ($body as $b) {
        $li = ($b['t'] ?? 'p') === 'li';
        if ($out && !($li && $prevLi)) $out[] = '';
        $out[] = ($li ? '- ' : '') . (string) ($b['x'] ?? '');
        $prevLi = $li;
    }
    return implode("\n", $out);
}

function text_to_body(string $text): array
{
    $body = [];
    foreach (preg_split('/\R\s*\R/', trim($text)) as $chunk) {
        $para = [];
        foreach (preg_split('/\R/', $chunk) as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (preg_match('/^[-•*]\s+(.+)$/u', $line, $m)) {
                if ($para) { $body[] = ['t' => 'p', 'x' => implode(' ', $para)]; $para = []; }
                $body[] = ['t' => 'li', 'x' => mb_substr($m[1], 0, 2000)];
            } else {
                $para[] = $line;
            }
        }
        if ($para) $body[] = ['t' => 'p', 'x' => mb_substr(implode(' ', $para), 0, 5000)];
    }
    return $body;
}

/** Every uploaded file from a multiple file input, one at a time as $_FILES['__one']. */
function each_upload(string $field): Generator
{
    $f = $_FILES[$field] ?? null;
    if (!is_array($f) || !is_array($f['name'] ?? null)) return;
    foreach ($f['name'] as $i => $name) {
        $_FILES['__one'] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        yield $name;
    }
    unset($_FILES['__one']);
}

function admin_page_content(): void
{
    $view = get_str('view', 20);
    $slug = get_str('slug', 80);
    if ($view === 'testimonials') admin_testimonials();
    if ($slug !== '') admin_content_page($slug);
    admin_content_list();
}

function admin_content_list(): void
{
    $pages = data_read('pages')['pages'] ?? [];
    $groups = '';
    foreach (PAGE_TYPES as $type => [$label]) {
        $rows = '';
        foreach ($pages as $slug => $p) {
            if (($p['type'] ?? '') !== $type) continue;
            $first = $p['images'][0]['src'] ?? null;
            $rows .= '<tr data-name="' . h(mb_strtolower($p['title'] . ' ' . $slug)) . '">'
                . '<td data-label="Page"><a class="item-link" href="' . h(admin_url('content', ['slug' => $slug])) . '">'
                . ($first ? '<img class="thumb sm" src="' . h(img_url($first)) . '" alt="" loading="lazy">' : '<span class="thumb sm none">' . icon('edit', 16) . '</span>')
                . '<span><b>' . h($p['title']) . '</b><span class="cell-sub">/' . h(PAGE_TYPES[$type][1] . $slug) . '/</span></span></a></td>'
                . '<td data-label="Text" class="q">' . h(snip((string) ($p['body'][0]['x'] ?? ''), 90)) . '</td>'
                . '<td data-label="Photos" class="num">' . count($p['images'] ?? []) . '</td>'
                . '<td class="act">' . btn_link('Edit', admin_url('content', ['slug' => $slug]), 'sm ghost', 'edit') . ' '
                . btn_link('View', page_url($slug, $p), 'sm ghost', 'external', ' target="_blank" rel="noopener"') . '</td></tr>';
        }
        if ($rows !== '') $groups .= '<tr class="cat"><td colspan="4">' . h($label) . '</td></tr>' . $rows;
    }
    $t = data_read('testimonials');
    $body = page_head('Page content', 'The words and photos on the website\'s pages. Changes show on the website as soon as you save.')
        . '<div class="callout">' . icon('chat', 18) . '<div><b>Testimonials</b><p class="muted">' . h(plural(count($t), 'customer review')) . ' on the home and testimonials pages.</p></div>'
        . '<div class="callout-act">' . btn_link('Edit testimonials', admin_url('content', ['view' => 'testimonials']), 'sm primary', 'edit') . '</div></div>'
        . '<div class="toolbar"><label class="search">' . icon('search', 16) . '<input type="search" data-filter="#pages-table" placeholder="Filter pages…" aria-label="Filter pages"></label>'
        . '<span class="grow"></span><span class="muted" data-filter-count="#pages-table">' . h(plural(count($pages), 'page')) . '</span></div>'
        . '<div class="table stack"><table class="tbl stack" id="pages-table"><thead><tr><th>Page</th><th>Text</th><th class="num">Photos</th><th></th></tr></thead><tbody>' . $groups . '</tbody></table></div>'
        . '<p class="muted small">The home, about us and contact pages are part of the site\'s design. Business details on them (phone, address, hours) come from Store settings.</p>';
    admin_render('Page content', $body, 'content');
}

/** One page: edit and save. Ends the request. */
function admin_content_page(string $slug): void
{
    $data = data_read('pages');
    $back = '<p class="back"><a href="' . h(admin_url('content')) . '">' . icon('left', 15) . ' Page content</a></p>';
    if (!isset($data['pages'][$slug])) admin_render('Page', $back . empty_state('Page not found', '', 'edit'), 'content');
    $p = $data['pages'][$slug];

    if (is_post()) {
        require_csrf();
        $self = admin_url('content', ['slug' => $slug]);
        $title = trim(post_str('title', 120));
        if ($title === '') {
            flash('The page needs a title.', 'err');
            redirect($self);
        }
        // Big galleries post three fields per photo. If PHP's max_input_vars cut the form short, save nothing.
        if (count($p['images'] ?? []) && count((array) ($_POST['ipos'] ?? [])) < count($p['images']) || !isset($_POST['description'])) {
            flash('This page has too many photos for the server form limit, so nothing was saved. Raise max_input_vars (see website/.user.ini) and try again.', 'err');
            redirect($self);
        }
        $p['title'] = $title;
        foreach (['menu' => 60, 'heading' => 200, 'link' => 300] as $k => $max) {
            $v = trim(post_str($k, $max));
            if ($k === 'link' && $v !== '' && !preg_match('#^https?://#i', $v)) $v = '';
            if ($v !== '') $p[$k] = $v; else unset($p[$k]);
        }
        $p['description'] = trim(post_str('description', 320));
        $p['body'] = text_to_body((string) ($_POST['body'] ?? ''));

        // Photos: captions, order, removals, then new uploads at the end.
        $imgs = [];
        foreach ($p['images'] ?? [] as $i => $im) {
            if (!empty($_POST['idel'][$i])) continue;
            $alt = trim(mb_substr((string) ($_POST['ialt'][$i] ?? ''), 0, 200));
            $imgs[] = ['pos' => (int) ($_POST['ipos'][$i] ?? $i + 1) * 1000 + $i, 'src' => $im['src'], 'alt' => $alt !== '' ? $alt : null, 'title' => $im['title'] ?? null];
        }
        usort($imgs, fn($a, $b) => $a['pos'] <=> $b['pos']);
        // Gallery photos keep their own name (e.g. "Birthday & Novelty Cake #12").
        $imgs = array_map(fn($x) => ['src' => $x['src'], 'alt' => $x['alt']] + ($x['title'] !== null ? ['title' => $x['title']] : []), $imgs);

        $added = 0;
        $errors = [];
        $manifest = json_read(UPLOADS_DIR . '/manifest.json', []);
        $sub = 'admin/' . date('Y/m');
        foreach (each_upload('photos') as $name) {
            $up = admin_save_upload('__one', UPLOADS_DIR . '/' . $sub, file_slug(pathinfo((string) $name, PATHINFO_FILENAME), 40) . '-' . bin2hex(random_bytes(3)), 1600, true);
            if (!empty($up['file'])) {
                $key = $sub . '/' . $up['file'];
                $manifest[$key] = ['w' => $up['w'], 'h' => $up['h'], 'file' => $key] + ($up['sm'] ? ['sm' => true] : []);
                $imgs[] = ['src' => $key, 'alt' => null];
                $added++;
            } elseif ($up['error'] !== '') {
                $errors[] = $name . ': ' . $up['error'];
            }
        }
        if ($added) {
            json_write(UPLOADS_DIR . '/manifest.json', $manifest);
            admin_log('page.photo', $title . ': ' . plural($added, 'photo'));
        }
        $p['images'] = $imgs;
        $data['pages'][$slug] = $p;
        if (data_save('pages', $data)) {
            admin_log('page.save', $title);
            flash('Saved ' . $title . '.' . ($added ? ' ' . plural($added, 'photo') . ' added.' : ''));
        } else {
            flash('The page couldn\'t be saved. Check that website/data/pages.json can be written to.', 'err');
        }
        foreach ($errors as $e) flash($e, 'err');
        redirect($self);
    }

    $photos = '';
    foreach ($p['images'] ?? [] as $i => $im) {
        $photos .= '<div class="photo-row">'
            . '<a href="' . h(img_url($im['src'])) . '" target="_blank" rel="noopener"><img class="thumb md" src="' . h(img_url($im['src'])) . '" alt="" loading="lazy"></a>'
            . '<label class="field"><span>Description for screen readers and Google</span><input name="ialt[' . $i . ']" value="' . h($im['alt'] ?? '') . '" maxlength="200" placeholder="e.g. Three-tier white wedding cake with roses"></label>'
            . '<label class="field xs"><span>Order</span><input class="num-in" name="ipos[' . $i . ']" value="' . ($i + 1) . '" inputmode="numeric"></label>'
            . '<label class="check danger"><input type="checkbox" name="idel[' . $i . ']" value="1"> Remove</label></div>';
    }
    $isPolicy = $p['type'] === 'policy';
    $body = $back
        . page_head($p['title'], h(PAGE_TYPES[$p['type']][0]) . ' &middot; /' . h(PAGE_TYPES[$p['type']][1] . $slug) . '/',
            btn_link('View page', page_url($slug, $p), 'sm', 'external', ' target="_blank" rel="noopener"'))
        . '<form method="post" enctype="multipart/form-data" action="' . h(admin_url('content', ['slug' => $slug])) . '">' . csrf_field()
        . '<div class="split"><div class="stack">'
        .   card('Text', '<div class="form-grid">'
                . '<label class="field"><span>Title</span><input name="title" value="' . h($p['title']) . '" maxlength="120" required></label>'
                . (!$isPolicy ? '<label class="field"><span>Name in the menu <small class="muted">(optional, if shorter)</small></span><input name="menu" value="' . h($p['menu'] ?? '') . '" maxlength="60"></label>' : '')
                . (in_array($p['type'], ['occasion', 'bakery'], true) ? '<label class="field wide"><span>Headline</span><input name="heading" value="' . h($p['heading'] ?? '') . '" maxlength="200"></label>' : '')
                . '<label class="field wide"><span>Page text</span><textarea name="body" rows="14" class="prose-in">' . h(body_to_text($p['body'] ?? [])) . '</textarea>'
                . '<small class="muted">Leave an empty line between paragraphs. Start a line with "- " for a bullet point.</small></label>'
                . ($p['type'] === 'award' ? '<label class="field wide"><span>Link to the award\'s website <small class="muted">(optional)</small></span><input name="link" type="url" value="' . h($p['link'] ?? '') . '" maxlength="300"></label>' : '')
                . '</div>')
        .   ($isPolicy ? '' : card('Photos', ($photos !== '' ? '<div class="photo-rows">' . $photos . '</div>' : '<p class="muted">No photos yet.</p>')
                . '<label class="field upload"><span>Add photos</span><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>'
                . '<small class="muted">JPG, PNG or WebP. You can choose several at once; they are added at the end.</small></label>'))
        . '</div><div class="stack">'
        .   card('Google', '<label class="field wide"><span>Description in search results</span><textarea name="description" rows="5" maxlength="320" data-count="160">' . h($p['description'] ?? '') . '</textarea>'
                . '<small class="muted">About 150–160 characters. Say what the page offers and mention Mississauga.</small></label>')
        . '</div></div>'
        . '<div class="savebar"><span class="muted">Changes show on the website as soon as you save.</span>' . btn_submit('Save page', 'primary', '', '', 'check') . '</div></form>';
    admin_render($p['title'], $body, 'content');
}

/** Testimonials: edit, reorder, remove, add. Ends the request. */
function admin_testimonials(): void
{
    $list = data_read('testimonials');
    $self = admin_url('content', ['view' => 'testimonials']);
    if (is_post()) {
        require_csrf();
        $out = [];
        foreach ((array) ($_POST['tname'] ?? []) as $i => $name) {
            if (!empty($_POST['tdel'][$i])) continue;
            $t = [
                'name'     => trim(mb_substr((string) $name, 0, 80)),
                'occasion' => trim(mb_substr((string) ($_POST['tocc'][$i] ?? ''), 0, 80)),
                'short'    => trim(mb_substr((string) ($_POST['tshort'][$i] ?? ''), 0, 300)),
                'text'     => trim(mb_substr((string) ($_POST['ttext'][$i] ?? ''), 0, 3000)),
            ];
            if ($t['name'] === '' || $t['text'] === '') continue; // blank "new" rows
            if ($t['short'] === '') $t['short'] = snip($t['text'], 160);
            $out[] = ['pos' => (int) ($_POST['tpos'][$i] ?? 999) * 1000 + (int) $i, 't' => $t];
        }
        usort($out, fn($a, $b) => $a['pos'] <=> $b['pos']);
        if (data_save('testimonials', array_column($out, 't'))) {
            admin_log('testimonials', plural(count($out), 'testimonial'));
            flash('Testimonials saved.');
        } else {
            flash('The testimonials couldn\'t be saved.', 'err');
        }
        redirect($self);
    }
    $cards = '';
    foreach (array_merge($list, [[]]) as $i => $t) {
        $new = $t === [];
        $cards .= '<section class="card' . ($new ? ' addcard' : '') . '"><header class="card-head"><h2>' . ($new ? icon('plus', 16) . ' Add a testimonial' : h($t['name'])) . '</h2>'
            . (!$new ? '<label class="check danger"><input type="checkbox" name="tdel[' . $i . ']" value="1"> Remove</label>' : '') . '</header><div class="card-body"><div class="form-grid">'
            . '<label class="field"><span>Name</span><input name="tname[' . $i . ']" value="' . h($t['name'] ?? '') . '" maxlength="80"></label>'
            . '<label class="field"><span>Cake / occasion</span><input name="tocc[' . $i . ']" value="' . h($t['occasion'] ?? '') . '" maxlength="80" placeholder="Wedding cake"></label>'
            . '<label class="field xs"><span>Order</span><input class="num-in" name="tpos[' . $i . ']" value="' . ($i + 1) . '" inputmode="numeric"></label>'
            . '<label class="field wide"><span>Their words</span><textarea name="ttext[' . $i . ']" rows="4" maxlength="3000">' . h($t['text'] ?? '') . '</textarea></label>'
            . '<label class="field wide"><span>Short version for the home page <small class="muted">(optional)</small></span><input name="tshort[' . $i . ']" value="' . h($t['short'] ?? '') . '" maxlength="300"></label>'
            . '</div></div></section>';
    }
    $body = '<p class="back"><a href="' . h(admin_url('content')) . '">' . icon('left', 15) . ' Page content</a></p>'
        . page_head('Testimonials', 'Customer reviews on the home page and the testimonials page.', btn_link('View page', SITE_BASE . 'testimonials/', 'sm', 'external', ' target="_blank" rel="noopener"'))
        . form_open($self) . '<div class="stack">' . $cards . '</div>'
        . '<div class="savebar"><span class="muted">Changes show on the website as soon as you save.</span>' . btn_submit('Save testimonials', 'primary', '', '', 'check') . '</div></form>';
    admin_render('Testimonials', $body, 'content');
}
