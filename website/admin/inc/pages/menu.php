<?php
/**
 * Menu & prices: the online order page's menu (data/menu.json).
 *
 *   The list     switch items on/off, change prices, mark sold out or "most ordered", all in one save
 *   ?item=ID     one item: name, description, category, sizes and prices, flavour choice, photo
 *   Categories   names, order, HST on/off, hide a whole category
 *
 * Prices are what the customer pays at pickup (HST is added on taxable categories).
 * api/order.php re-prices every order from this file, so changes apply straight away.
 */
if (!defined('ADMIN')) { http_response_code(403); exit('Forbidden'); }

const MENU_PHOTO_DIR = __DIR__ . '/../../../images/menu';

function cents_field(int $cents): string
{
    return number_format($cents / 100, 2, '.', '');
}

/** [category index, product index] of a product id, or null. */
function menu_locate(array $menu, string $id): ?array
{
    foreach ($menu['categories'] as $ci => $c) {
        foreach ($c['products'] as $pi => $p) if ($p['id'] === $id) return [$ci, $pi];
    }
    return null;
}

function menu_all_ids(array $menu): array
{
    $ids = [];
    foreach ($menu['categories'] as $c) {
        foreach ($c['products'] as $p) {
            $ids[$p['id']] = true;
            foreach ($p['variants'] as $v) $ids['v:' . $v['id']] = true;
        }
    }
    return $ids;
}

/** A new id for a product (from its name) or a size, not used anywhere in the menu yet. */
function menu_new_id(array $menu, string $name = ''): string
{
    $taken = menu_all_ids($menu);
    if ($name === '') {
        do { $id = 'v' . bin2hex(random_bytes(5)); } while (isset($taken['v:' . $id]));
        return $id;
    }
    $base = file_slug($name, 60);
    $id = $base;
    for ($n = 2; isset($taken[$id]); $n++) $id = $base . '-' . $n;
    return $id;
}

function menu_save(array $menu, string $action, string $detail): bool
{
    $menu['updated'] = date('Y-m-d');
    if (!data_save('menu', $menu)) {
        flash('The menu couldn\'t be saved. Check that website/data/menu.json can be written to.', 'err');
        return false;
    }
    admin_log($action, $detail);
    return true;
}

function admin_page_menu(): void
{
    $menu = data_read('menu');
    if (empty($menu['categories'])) admin_render('Menu & prices', page_head('Menu & prices') . empty_state('The menu file is missing', 'website/data/menu.json couldn\'t be read.', 'list'), 'menu');

    $id = get_str('item', 80);
    if (is_post()) {
        require_csrf();
        $op = (string) ($_POST['op'] ?? '');
        if ($op === 'item' || $op === 'delete') admin_menu_item_post($menu, $id, $op); // redirects
        if ($op === 'add') admin_menu_add($menu);                                      // redirects
        if ($op === 'categories') admin_menu_categories_post($menu);                   // redirects
        admin_menu_list_post($menu);                                                   // redirects
    }
    if ($id !== '') admin_menu_item_page($menu, $id); // ends the request
    admin_menu_list_page($menu);
}

// ---------- saving ----------

/** The list form: on/off, prices, sold out, most ordered. */
function admin_menu_list_post(array $menu): void
{
    if (empty($_POST['end'])) {
        // The server drops form fields past max_input_vars; saving a cut-off form would lose settings.
        flash('Nothing was saved: the menu form is bigger than this server accepts (max_input_vars is ' . ini_get('max_input_vars') . '). Ask your web host to raise it.', 'err');
        redirect(admin_url('menu'));
    }
    $posted = array_flip(array_map('strval', (array) ($_POST['ids'] ?? [])));
    $bad = [];
    $changed = 0;
    foreach ($menu['categories'] as &$c) {
        foreach ($c['products'] as &$p) {
            if (!isset($posted[$p['id']])) continue;
            $before = json_encode($p);
            $p['hidden'] = !isset($_POST['avail'][$p['id']]);
            $p['soldOut'] = !empty($_POST['sold'][$p['id']]);
            $p['popular'] = !empty($_POST['pop'][$p['id']]);
            foreach ($p['variants'] as &$v) {
                $raw = $_POST['price'][$p['id']][$v['id']] ?? null;
                if (!is_string($raw)) continue;
                $cents = dollars_to_cents($raw);
                if ($cents === null || $cents < 0 || $cents > 500000) $bad[] = $p['name'];
                else $v['price'] = $cents;
            }
            unset($v);
            foreach (['hidden', 'soldOut', 'popular'] as $k) if (!$p[$k]) unset($p[$k]);
            if (json_encode($p) !== $before) $changed++;
        }
        unset($p);
    }
    unset($c);
    if ($changed && menu_save($menu, 'menu.quick', plural($changed, 'item') . ' changed')) {
        flash('Saved ' . plural($changed, 'change') . '. The order page is updated.');
    } elseif (!$changed && !$bad) {
        flash('Nothing had changed.');
    }
    if ($bad) flash('Check the price for ' . implode(', ', array_unique($bad)) . ' (use a number like 12.50). Those prices were not changed.', 'err');
    redirect(admin_url('menu'));
}

/** Add a new item, then open it to fill in the rest. */
function admin_menu_add(array $menu): void
{
    $name = trim(post_str('name', 80));
    $price = dollars_to_cents((string) ($_POST['price'] ?? ''));
    $cat = (string) ($_POST['category'] ?? '');
    $ci = array_search($cat, array_column($menu['categories'], 'id'), true);
    if ($name === '' || $price === null || $price < 0 || $ci === false) {
        flash('Give the new item a name, a category and a price.', 'err');
        redirect(admin_url('menu', ['add' => 1]));
    }
    $id = menu_new_id($menu, $name);
    $menu['categories'][$ci]['products'][] = [
        'id'       => $id,
        'name'     => $name,
        'desc'     => trim(post_str('desc', 400)),
        'img'      => null,
        'variants' => [['id' => menu_new_id($menu), 'label' => null, 'price' => $price]],
        'hidden'   => true, // stays off the order page until it's switched on
    ];
    if (menu_save($menu, 'menu.add', $name)) flash('Added ' . $name . '. Add a photo and sizes if you like, then switch it on to show it on the order page.');
    redirect(admin_url('menu', ['item' => $id]));
}

/** Save or delete one item from its own page. */
function admin_menu_item_post(array $menu, string $id, string $op): void
{
    $at = menu_locate($menu, $id);
    if (!$at) {
        flash('That item wasn\'t found.', 'err');
        redirect(admin_url('menu'));
    }
    [$ci, $pi] = $at;
    $p = $menu['categories'][$ci]['products'][$pi];

    if ($op === 'delete') {
        array_splice($menu['categories'][$ci]['products'], $pi, 1);
        if (menu_save($menu, 'menu.delete', $p['name'])) flash('Deleted ' . $p['name'] . '.');
        redirect(admin_url('menu'));
    }

    $back = admin_url('menu', ['item' => $id]);
    $name = trim(post_str('name', 80));
    if ($name === '') {
        flash('The item needs a name.', 'err');
        redirect($back);
    }

    // Sizes and prices: existing rows (or deleted), then the blank rows for new ones.
    $variants = [];
    $errors = [];
    foreach ($p['variants'] as $v) {
        if (!empty($_POST['vdel'][$v['id']])) continue;
        $cents = dollars_to_cents((string) ($_POST['vprice'][$v['id']] ?? ''));
        if ($cents === null || $cents < 0 || $cents > 500000) {
            $errors[] = 'Check the price for ' . ($v['label'] ?: 'the item') . '.';
            $cents = $v['price'];
        }
        $label = trim(mb_substr((string) ($_POST['vlabel'][$v['id']] ?? ''), 0, 40));
        $variants[] = ['id' => $v['id'], 'label' => $label !== '' ? $label : null, 'price' => $cents];
    }
    foreach ((array) ($_POST['nlabel'] ?? []) as $k => $label) {
        $label = trim(mb_substr((string) $label, 0, 40));
        $rawPrice = trim((string) ($_POST['nprice'][$k] ?? ''));
        if ($label === '' && $rawPrice === '') continue;
        $cents = dollars_to_cents($rawPrice);
        if ($cents === null || $cents < 0 || $cents > 500000) {
            $errors[] = 'The new size "' . $label . '" needs a price.';
            continue;
        }
        $variants[] = ['id' => menu_new_id($menu), 'label' => $label !== '' ? $label : null, 'price' => $cents];
    }
    if (!$variants) {
        flash('An item needs at least one price, so the last size wasn\'t removed.', 'err');
        redirect($back);
    }
    if (count($variants) > 1) {
        foreach ($variants as $v) if ($v['label'] === null) { $errors[] = 'When an item has more than one size, give each size a name (like Small, 15 cm or 6 pcs).'; break; }
    }

    $p['name'] = $name;
    $p['desc'] = trim(post_str('desc', 400));
    $p['variants'] = $variants;
    foreach (['hidden' => empty($_POST['avail']), 'soldOut' => !empty($_POST['sold']), 'popular' => !empty($_POST['pop'])] as $k => $on) {
        if ($on) $p[$k] = true; else unset($p[$k]);
    }

    // A choice customers must make (flavour, filling…): one option per line.
    $options = array_values(array_unique(array_filter(array_map(fn($s) => trim(mb_substr($s, 0, 60)), preg_split('/\R/', (string) ($_POST['options'] ?? ''))), 'strlen')));
    if (count($options) >= 2) {
        $p['choice'] = ['label' => trim(post_str('choice', 40)) ?: 'Flavour', 'options' => array_slice($options, 0, 40)];
    } else {
        unset($p['choice']);
        if (count($options) === 1) $errors[] = 'A choice needs at least two options, so it was removed.';
    }

    // Photo.
    if (!empty($_POST['nophoto'])) $p['img'] = null;
    $up = admin_save_upload('photo', MENU_PHOTO_DIR, $id . '-' . time(), 900);
    if (!empty($up['file'])) {
        $old = (string) ($p['img'] ?? '');
        if (preg_match('#^images/menu/' . preg_quote($id, '#') . '-\d{10}\.(webp|jpe?g|png)$#', $old)) @unlink(MENU_PHOTO_DIR . '/' . basename($old));
        $p['img'] = 'images/menu/' . $up['file'];
        admin_log('menu.photo', $name);
    } elseif (!empty($up['error'])) {
        $errors[] = $up['error'];
    }

    // Category (moving puts the item at the end of the new category).
    $newCat = array_search((string) ($_POST['category'] ?? ''), array_column($menu['categories'], 'id'), true);
    if ($newCat !== false && $newCat !== $ci) {
        array_splice($menu['categories'][$ci]['products'], $pi, 1);
        $menu['categories'][$newCat]['products'][] = $p;
    } else {
        $menu['categories'][$ci]['products'][$pi] = $p;
    }

    if (menu_save($menu, 'menu.product', $name)) flash('Saved ' . $name . '.' . (!empty($p['hidden']) ? ' It is switched off, so customers don\'t see it yet.' : ' The order page is updated.'));
    foreach ($errors as $e) flash($e, 'err');
    redirect($back);
}

function admin_menu_categories_post(array $menu): void
{
    $order = [];
    foreach ($menu['categories'] as $i => &$c) {
        $name = trim(mb_substr((string) ($_POST['cname'][$c['id']] ?? $c['name']), 0, 60));
        if ($name !== '') $c['name'] = $name;
        $c['taxable'] = !empty($_POST['ctax'][$c['id']]);
        if (!empty($_POST['chide'][$c['id']])) $c['hidden'] = true; else unset($c['hidden']);
        if (empty($_POST['cdine'][$c['id']])) $c['noDineIn'] = true; else unset($c['noDineIn']);
        $order[$i] = (int) ($_POST['corder'][$c['id']] ?? ($i + 1)) * 1000 + $i;
    }
    unset($c);
    asort($order);
    $menu['categories'] = array_values(array_map(fn($i) => $menu['categories'][$i], array_keys($order)));
    if (menu_save($menu, 'menu.category', 'names, order and HST')) flash('Categories saved.');
    redirect(admin_url('menu') . '#categories');
}

// ---------- pages ----------

function menu_thumb(?string $img, string $size = 'sm'): string
{
    return $img
        ? '<img class="thumb ' . $size . '" src="' . h(SITE_BASE . $img) . '" alt="" loading="lazy">'
        : '<span class="thumb ' . $size . ' none">' . icon('bag', $size === 'sm' ? 16 : 28) . '</span>';
}

function admin_menu_list_page(array $menu): void
{
    $catOptions = fn($sel) => implode('', array_map(fn($c) => '<option value="' . h($c['id']) . '"' . ($c['id'] === $sel ? ' selected' : '') . '>' . h($c['name']) . '</option>', $menu['categories']));

    $rows = '';
    $count = $off = $sold = 0;
    foreach ($menu['categories'] as $c) {
        $rows .= '<tr class="cat"><td colspan="6">' . h($c['name'])
            . ($c['taxable'] ? ' <span class="chip">+ HST</span>' : '') . (!empty($c['hidden']) ? ' <span class="badge tone-crit">hidden</span>' : '') . '</td></tr>';
        foreach ($c['products'] as $p) {
            $count++;
            $isOff = !empty($p['hidden']) || !empty($c['hidden']);
            if ($isOff) $off++;
            if (!empty($p['soldOut'])) $sold++;
            $prices = '';
            foreach ($p['variants'] as $v) {
                $prices .= '<label class="fp"><span class="fp-lbl">' . h($v['label'] ?? 'Price') . '</span>' . money_input('price[' . $p['id'] . '][' . $v['id'] . ']', cents_field((int) $v['price'])) . '</label>';
            }
            $pid = h($p['id']);
            $rows .= '<tr class="' . ($isOff ? 'is-off' : '') . '" data-name="' . h(mb_strtolower($p['name'] . ' ' . $c['name'])) . '">'
                . '<td data-label="On"><label class="switch"><input type="checkbox" name="avail[' . $pid . ']" value="1" ' . (!empty($p['hidden']) ? '' : 'checked') . ' aria-label="Show ' . h($p['name']) . ' on the order page"><span></span></label>'
                . '<input type="hidden" name="ids[]" value="' . $pid . '"></td>'
                . '<td class="fname" data-label="Item"><a class="item-link" href="' . h(admin_url('menu', ['item' => $p['id']])) . '">' . menu_thumb($p['img'] ?? null) . '<span>' . h($p['name'])
                . (!empty($p['choice']) ? ' <span class="chip">' . h(plural(count($p['choice']['options']), strtolower($p['choice']['label']))) . '</span>' : '') . '</span></a></td>'
                . '<td data-label="Price"><div class="fprices">' . $prices . '</div></td>'
                . '<td data-label="Sold out"><label class="check"><input type="checkbox" name="sold[' . $pid . ']" value="1" ' . (!empty($p['soldOut']) ? 'checked' : '') . '> Sold out</label></td>'
                . '<td data-label="Most ordered"><label class="check"><input type="checkbox" name="pop[' . $pid . ']" value="1" ' . (!empty($p['popular']) ? 'checked' : '') . '> Most ordered</label></td>'
                . '<td class="act">' . btn_link('Edit', admin_url('menu', ['item' => $p['id']]), 'sm ghost', 'edit') . '</td></tr>';
        }
    }

    $add = '<details class="card addcard"' . (get_str('add', 1) === '1' ? ' open' : '') . '><summary><span>' . icon('plus', 16) . ' Add a new item</span><small>It starts switched off, so you can add a photo first</small></summary><div class="card-body">'
        . form_open(admin_url('menu')) . '<input type="hidden" name="op" value="add">'
        . '<div class="form-grid">'
        . '<label class="field"><span>Name</span><input name="name" maxlength="80" required></label>'
        . '<label class="field sm"><span>Price</span>' . money_input('price', '', '0.00', true) . '</label>'
        . '<label class="field sm"><span>Category</span><select name="category">' . $catOptions('') . '</select></label>'
        . '<label class="field wide"><span>Description (optional)</span><input name="desc" maxlength="400"></label>'
        . '</div><div class="bar">' . btn_submit('Add item', 'primary', '', '', 'plus') . '</div></form></div></details>';

    $catRows = '';
    foreach ($menu['categories'] as $i => $c) {
        $cid = h($c['id']);
        $catRows .= '<tr><td data-label="Order"><input class="num-in" name="corder[' . $cid . ']" value="' . ($i + 1) . '" inputmode="numeric" aria-label="Position"></td>'
            . '<td data-label="Name"><input name="cname[' . $cid . ']" value="' . h($c['name']) . '" maxlength="60" aria-label="Category name"></td>'
            . '<td data-label="Items" class="muted">' . count($c['products']) . '</td>'
            . '<td data-label="HST"><label class="check"><input type="checkbox" name="ctax[' . $cid . ']" value="1" ' . ($c['taxable'] ? 'checked' : '') . '> Add HST</label></td>'
            . '<td data-label="Tables"><label class="check"><input type="checkbox" name="cdine[' . $cid . ']" value="1" ' . (empty($c['noDineIn']) ? 'checked' : '') . '> Table ordering</label></td>'
            . '<td data-label="Hidden"><label class="check danger"><input type="checkbox" name="chide[' . $cid . ']" value="1" ' . (!empty($c['hidden']) ? 'checked' : '') . '> Hide category</label></td></tr>';
    }
    $cats = '<div id="categories">' . card('Categories', form_open(admin_url('menu')) . '<input type="hidden" name="op" value="categories">'
        . '<p class="muted small">Change a number to move a category (1 = first on the order page). HST (' . h(rtrim(rtrim(number_format(($menu['taxRate'] ?? 0.13) * 100, 2), '0'), '.')) . '%) is added to items in categories marked "Add HST"; bakery food is usually zero-rated. Untick "Table ordering" for categories guests can\'t order at a table (e.g. whole cakes).</p>'
        . '<div class="table flush stack"><table class="tbl ftable compact stack"><thead><tr><th>Order</th><th>Name</th><th>Items</th><th>HST</th><th>Tables</th><th></th></tr></thead><tbody>' . $catRows . '</tbody></table></div>'
        . '<div class="bar">' . btn_submit('Save categories', 'primary') . '</div></form>') . '</div>';

    $closed = !menu_ordering_open($menu);
    $body = page_head('Menu & prices', 'The menu on the website\'s order page. Prices are what customers pay at pickup; switch an item off to hide it, or mark it sold out for the day.',
            btn_link('View order page', SITE_BASE . 'order/', 'sm', 'external', ' target="_blank" rel="noopener"'))
        . ($closed ? '<div class="callout">' . icon('alert', 18) . '<div><b>Online ordering is switched off</b><p class="muted">The menu is shown, but customers can\'t order. Turn it on in Store settings.</p></div><div class="callout-act">' . btn_link('Store settings', admin_url('settings') . '#ordering', 'sm') . '</div></div>' : '')
        . $add
        . form_open(admin_url('menu'), 'menuform') . '<input type="hidden" name="op" value="list">'
        . '<div class="toolbar"><label class="search">' . icon('search', 16) . '<input type="search" data-filter="#menu-table" placeholder="Filter items…" aria-label="Filter items"></label>'
        . '<span class="grow"></span><span class="muted" data-filter-count="#menu-table">' . plural($count, 'item') . ($off ? ', ' . $off . ' off' : '') . ($sold ? ', ' . $sold . ' sold out' : '') . '</span></div>'
        . '<div class="table stack"><table class="tbl ftable stack" id="menu-table"><thead><tr><th>On</th><th>Item</th><th>Price</th><th>Sold out</th><th>Most ordered</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table></div>'
        . '<input type="hidden" name="end" value="1">'
        . '<div class="savebar"><span class="muted">Changes go live as soon as you save.</span>' . btn_submit('Save changes', 'primary', '', '', 'check') . '</div></form>'
        . $cats;
    admin_render('Menu & prices', $body, 'menu');
}

/** One item's page. Ends the request. */
function admin_menu_item_page(array $menu, string $id): void
{
    $back = '<p class="back"><a href="' . h(admin_url('menu')) . '">' . icon('left', 15) . ' Menu &amp; prices</a></p>';
    $at = menu_locate($menu, $id);
    if (!$at) admin_render('Menu item', $back . empty_state('Item not found', 'It may have been deleted.', 'list'), 'menu');
    [$ci, $pi] = $at;
    $c = $menu['categories'][$ci];
    $p = $c['products'][$pi];

    $catOptions = implode('', array_map(fn($x) => '<option value="' . h($x['id']) . '"' . ($x['id'] === $c['id'] ? ' selected' : '') . '>' . h($x['name']) . '</option>', $menu['categories']));
    $sizes = '';
    foreach ($p['variants'] as $v) {
        $vid = h($v['id']);
        $sizes .= '<tr><td data-label="Size"><input name="vlabel[' . $vid . ']" value="' . h($v['label'] ?? '') . '" maxlength="40" placeholder="' . (count($p['variants']) > 1 ? 'Size name' : 'One size (leave blank)') . '" aria-label="Size name"></td>'
            . '<td data-label="Price">' . money_input('vprice[' . $v['id'] . ']', cents_field((int) $v['price']), '', true) . '</td>'
            . '<td data-label=""><label class="check danger"><input type="checkbox" name="vdel[' . $vid . ']" value="1"> Remove</label></td></tr>';
    }
    for ($k = 0; $k < 2; $k++) {
        $sizes .= '<tr class="new"><td data-label="New size"><input name="nlabel[' . $k . ']" maxlength="40" placeholder="Add a size, e.g. 20 cm" aria-label="New size name"></td>'
            . '<td data-label="Price">' . money_input('nprice[' . $k . ']', '', '0.00') . '</td><td></td></tr>';
    }

    $hasChoice = !empty($p['choice']);
    $img = $p['img'] ?? null;
    $body = $back
        . page_head($p['name'], h($c['name']) . ($c['taxable'] ? ' &middot; HST added' : ''),
            (!empty($p['hidden']) || !empty($c['hidden']) ? badge('Off the order page', 'muted') : badge('On the order page', 'good'))
            . (!empty($p['soldOut']) ? ' ' . badge('Sold out', 'crit') : '')
            . btn_link('View order page', SITE_BASE . 'order/', 'sm', 'external', ' target="_blank" rel="noopener"'))
        . '<form method="post" enctype="multipart/form-data" action="' . h(admin_url('menu', ['item' => $id])) . '">' . csrf_field() . '<input type="hidden" name="op" value="item">'
        . '<div class="split"><div class="stack">'
        .   card('Details', '<div class="form-grid">'
                . '<label class="field"><span>Name</span><input name="name" value="' . h($p['name']) . '" maxlength="80" required></label>'
                . '<label class="field sm"><span>Category</span><select name="category">' . $catOptions . '</select></label>'
                . '<label class="field wide"><span>Description</span><textarea name="desc" rows="3" maxlength="400">' . h($p['desc'] ?? '') . '</textarea></label></div>')
        .   card('Sizes & prices', '<p class="muted small">One price, or one row per size. Customers pick a size when there is more than one.</p>'
                . '<div class="table flush stack"><table class="tbl ftable compact stack"><thead><tr><th>Size</th><th>Price</th><th></th></tr></thead><tbody>' . $sizes . '</tbody></table></div>')
        .   card('Choice', '<p class="muted small">Something customers must choose, like a flavour. Leave the options empty for none.</p><div class="form-grid">'
                . '<label class="field sm"><span>Question</span><input name="choice" value="' . h($hasChoice ? $p['choice']['label'] : 'Flavour') . '" maxlength="40"></label>'
                . '<label class="field wide"><span>Options, one per line</span><textarea name="options" rows="5" placeholder="Vanilla&#10;Chocolate">' . h($hasChoice ? implode("\n", $p['choice']['options']) : '') . '</textarea></label></div>')
        . '</div><div class="stack">'
        .   card('On the order page', '<div class="checks">'
                . '<label class="check"><input type="checkbox" name="avail" value="1" ' . (empty($p['hidden']) ? 'checked' : '') . '> Show on the order page</label>'
                . '<label class="check"><input type="checkbox" name="sold" value="1" ' . (!empty($p['soldOut']) ? 'checked' : '') . '> Sold out (shown, can\'t be ordered)</label>'
                . '<label class="check"><input type="checkbox" name="pop" value="1" ' . (!empty($p['popular']) ? 'checked' : '') . '> In "Most ordered" at the top</label>'
                . '</div>' . (!empty($c['hidden']) ? '<p class="muted small">The whole ' . h($c['name']) . ' category is hidden, so this item isn\'t shown either.</p>' : ''))
        .   card('Photo', '<div class="photo-pick">' . menu_thumb($img, 'lg')
                . '<label class="field"><span>' . ($img ? 'Replace photo' : 'Add a photo') . '</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>'
                . ($img ? '<label class="check danger"><input type="checkbox" name="nophoto" value="1"> Remove photo</label>' : '')
                . '<p class="muted small">JPG, PNG or WebP. A landscape photo (4:3) looks best.</p></div>')
        . '</div></div>'
        . '<div class="savebar"><span class="muted">Changes go live as soon as you save.</span>' . btn_submit('Save item', 'primary', '', '', 'check') . '</div></form>'
        . form_open(admin_url('menu', ['item' => $id]), 'danger-zone') . '<input type="hidden" name="op" value="delete">'
        . btn_submit('Delete this item', 'sm danger', '', '', 'trash', 'Delete ' . $p['name'] . ' from the menu? Switching it off hides it without deleting.') . '</form>';
    admin_render($p['name'], $body, 'menu');
}
