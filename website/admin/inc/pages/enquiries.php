<?php
/**
 * Cake enquiries from the contact page form (custom cakes, weddings, events).
 * Saved by api/contact.php; the team adds a status and a note here.
 */
if (!defined('ADMIN')) { http_response_code(403); exit('Forbidden'); }

function enquiries_filters(array $src): array
{
    $out = [];
    foreach (['s', 'q', 'pg'] as $k) {
        if (isset($src[$k]) && is_string($src[$k]) && $src[$k] !== '') $out[$k] = mb_substr($src[$k], 0, 80);
    }
    return $out;
}

function admin_page_enquiries(): void
{
    if (is_post()) admin_enquiries_post();
    $id = get_str('id', 30);
    if ($id !== '') admin_enquiry_detail($id); // ends the request
    admin_enquiries_list();
}

function admin_enquiries_post(): void
{
    require_csrf();
    $id = post_str('id', 30);
    $e = enquiry_find($id);
    if (!$e) {
        flash('That enquiry wasn\'t found.', 'err');
        redirect(admin_url('enquiries'));
    }
    $who = $e['name'] !== '' ? $e['name'] : 'no name';
    switch ($_POST['action'] ?? '') {
        case 'status':
            $status = (string) ($_POST['status'] ?? '');
            if (isset(ENQUIRY_STATUSES[$status]) && enquiry_set($id, ['status' => $status])) {
                admin_log('enquiry.status', $who . ': ' . strtolower(ENQUIRY_STATUSES[$status][0]));
                flash('Enquiry marked ' . strtolower(ENQUIRY_STATUSES[$status][0]) . '.');
            }
            break;
        case 'note':
            enquiry_set($id, ['team_note' => post_str('note', 2000)]);
            admin_log('enquiry.note', $who);
            flash('Note saved.');
            break;
        case 'delete':
            if (enquiry_delete($id)) {
                admin_log('enquiry.delete', $who . ' (' . date('M j', $e['ts']) . ')');
                flash('Enquiry deleted.');
            }
            redirect(admin_url('enquiries'));
    }
    redirect(admin_url('enquiries', ['id' => $id]));
}

function admin_enquiry_detail(string $id): void
{
    $e = enquiry_find($id);

    // One of the customer's photos (?photo=1…): only for signed-in staff, from the private folder.
    if ($e && isset($_GET['photo'])) {
        $path = enquiry_photo_path($e, (int) $_GET['photo'] - 1);
        if (!$path) { http_response_code(404); exit('Photo not found'); }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif', 'heic' => 'image/heic', 'heif' => 'image/heif'][$ext] ?? 'application/octet-stream';
        $name = preg_replace('/[^\w.\- ]/u', '_', (string) ($e['photos'][(int) $_GET['photo'] - 1]['name'] ?? basename($path)));
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=86400');
        header('Content-Disposition: ' . (isset($_GET['download']) || in_array($ext, ['heic', 'heif'], true) ? 'attachment' : 'inline') . '; filename="' . $name . '"');
        readfile($path);
        exit;
    }

    $back ='<p class="back no-print"><a href="' . h(admin_url('enquiries')) . '">' . icon('left', 15) . ' Cake enquiries</a></p>';
    if (!$e) admin_render('Enquiry', $back . empty_state('Enquiry not found', 'It may have been deleted.', 'mail'), 'enquiries');

    // Opening a new enquiry marks it read.
    if ($e['status'] === 'new') {
        enquiry_set($id, ['status' => 'read']);
        $e['status'] = 'read';
    }

    $buttons = '';
    foreach (ENQUIRY_STATUSES as $key => [$label]) {
        if ($key === $e['status']) continue;
        $buttons .= btn_submit($key === 'new' ? 'Mark unread' : 'Mark ' . strtolower($label), 'sm' . ($key === 'booked' ? ' primary' : ''), 'status', $key);
    }
    $timeline = '<li><b>Received</b><span>' . h(fmt_when($e['ts'])) . '</span></li>';
    foreach ($e['history'] as $step) {
        if (!isset($step['s'], $step['t'], ENQUIRY_STATUSES[$step['s']])) continue;
        $timeline .= '<li><b>' . h(ENQUIRY_STATUSES[$step['s']][0]) . '</b><span>' . h(fmt_when((string) $step['t'])) . '</span></li>';
    }

    $first = trim(explode(' ', $e['name'])[0]);
    $reply = $e['email'] !== ''
        ? btn_link('Reply by email', mailto_href($e['email'], 'Your cake enquiry' . ($e['occasion'] !== '' ? ': ' . $e['occasion'] : '') . ' – La Sem Cakes',
            'Hi ' . ($first !== '' ? $first : 'there') . ",\n\nThank you for your enquiry" . ($e['occasion'] !== '' ? ' about a ' . $e['occasion'] : '') . ".\n\n\n\nLa Sem Cakes\n" . SITE['phone']), 'sm primary', 'mail')
        : '';
    $call = $e['phone'] !== '' ? btn_link('Call', 'tel:' . preg_replace('/[^\d+]/', '', $e['phone']), 'sm', 'phone') : '';

    $body = $back
        . page_head($e['name'] !== '' ? $e['name'] : 'Enquiry', h(fmt_when($e['ts'])) . ' &middot; ' . h(fmt_ago($e['ts'])),
            status_badge(ENQUIRY_STATUSES, $e['status']) . ' ' . $reply . $call)
        . '<div class="split"><div class="stack">'
        .   card('Request', kv([
                ['Cake / occasion', h($e['occasion'])],
                ['Date needed', h($e['date'] !== '' && strtotime($e['date']) ? date('l, F j, Y', strtotime($e['date'])) : $e['date'])],
                ['Guests', h($e['guests'])],
            ]) . '<div class="msgbody">' . nl2br(h($e['message'])) . '</div>')
        .   ($e['photos'] ? card('Photos from the customer (' . count($e['photos']) . ')', enquiry_photos_html($e)) : '')
        .   card('Note for the team', form_open(admin_url('enquiries'))
                . '<input type="hidden" name="id" value="' . h($id) . '"><input type="hidden" name="action" value="note">'
                . '<label class="field wide"><span class="sr">Note</span><textarea name="note" rows="4" maxlength="2000" placeholder="Only the team sees this: quote given, design ideas, deposit…">' . h($e['note']) . '</textarea></label>'
                . '<div class="bar">' . btn_submit('Save note', 'primary') . '</div></form>')
        . '</div><div class="stack">'
        .   card('Contact', kv([
                ['Name', h($e['name'])],
                ['Phone', $e['phone'] !== '' ? tel_link($e['phone']) : ''],
                ['Email', $e['email'] !== '' ? mail_link($e['email']) : ''],
            ]))
        .   card('Status', form_open(admin_url('enquiries'))
                . '<input type="hidden" name="id" value="' . h($id) . '"><input type="hidden" name="action" value="status">'
                . '<div class="btns">' . $buttons . '</div></form><ol class="timeline">' . $timeline . '</ol>'
                . '<p class="muted small">Booked = the customer is going ahead. Archive enquiries you\'re finished with.</p>')
        .   card('', form_open(admin_url('enquiries')) . '<input type="hidden" name="id" value="' . h($id) . '"><input type="hidden" name="action" value="delete">'
                . btn_submit('Delete enquiry', 'sm danger', '', '', 'trash', 'Delete this enquiry for good? This can\'t be undone.') . '</form>', '', 'no-print')
        . '</div></div>';
    admin_render('Enquiry', $body, 'enquiries');
}

function enquiry_photos_html(array $e): string
{
    $out = '<div class="enq-photos">';
    foreach ($e['photos'] as $i => $p) {
        $src = admin_url('enquiries', ['id' => $e['id'], 'photo' => $i + 1]);
        $name = (string) ($p['name'] ?? $p['file']);
        $out .= preg_match('/\.(heic|heif)$/i', $p['file'])
            ? '<a class="enq-photo enq-photo-file" href="' . h($src) . '">' . icon('download', 18) . '<span>' . h($name) . '</span></a>'
            : '<a class="enq-photo" href="' . h($src) . '" target="_blank" rel="noopener" title="' . h($name) . '"><img src="' . h($src) . '" alt="' . h($name) . '" loading="lazy"></a>';
    }
    return $out . '</div><p class="muted small">Click a photo to open it full size. HEIC photos (from iPhones) download instead.</p>';
}

function admin_enquiries_list(): void
{
    $views = [
        'open'     => ['Open',     fn($e) => in_array($e['status'], ['new', 'read', 'replied'], true)],
        'new'      => ['New',      fn($e) => $e['status'] === 'new'],
        'replied'  => ['Replied',  fn($e) => $e['status'] === 'replied'],
        'booked'   => ['Booked',   fn($e) => $e['status'] === 'booked'],
        'archived' => ['Archived', fn($e) => $e['status'] === 'archived'],
        'all'      => ['All',      fn($e) => true],
    ];
    $view = get_choice('s', array_keys($views), 'open');
    $q = get_str('q', 80);
    $matched = array_values(array_filter(enquiries_all(), fn($e) => $q === '' || stripos($e['name'] . ' ' . $e['email'] . ' ' . $e['phone'] . ' ' . $e['occasion'] . ' ' . $e['message'] . ' ' . $e['note'], $q) !== false));
    $shown = array_values(array_filter($matched, $views[$view][1]));
    $filters = enquiries_filters($_GET);
    unset($filters['pg']);

    if (isset($_GET['csv'])) {
        csv_download('lasem-enquiries', ['received', 'status', 'name', 'phone', 'email', 'cake / occasion', 'date needed', 'guests', 'message', 'team note'],
            array_map(fn($e) => [date('Y-m-d H:i', $e['ts']), $e['status'], $e['name'], $e['phone'], $e['email'], $e['occasion'], $e['date'], $e['guests'], $e['message'], $e['note']], $shown));
    }

    $tabs = [];
    foreach ($views as $key => [$label, $fn]) {
        $tabs[] = ['label' => $label, 'href' => admin_url('enquiries', array_diff_key($filters, ['s' => 1]) + ($key === 'open' ? [] : ['s' => $key])), 'on' => $key === $view, 'count' => count(array_filter($matched, $fn))];
    }
    [$pageRows, $pager] = paginate($shown, 50, 'enquiries', $filters);

    $rows = '';
    foreach ($pageRows as $e) {
        $soon = $e['date'] !== '' && strtotime($e['date']) && strtotime($e['date']) - time() < 7 * 86400 && strtotime($e['date']) >= strtotime('today');
        $rows .= '<tr' . ($e['status'] === 'new' ? ' class="unread"' : '') . '>'
            . '<td data-label="From"><a class="row-link" href="' . h(admin_url('enquiries', ['id' => $e['id']])) . '">' . guest_cell($e['name'], $e['email'] !== '' ? $e['email'] : $e['phone']) . '</a></td>'
            . '<td data-label="Cake">' . ($e['occasion'] !== '' ? '<b>' . h(snip($e['occasion'], 40)) . '</b>' : '') . '<div class="cell-sub">' . ($e['photos'] ? count($e['photos']) . (count($e['photos']) === 1 ? ' photo' : ' photos') . ' &middot; ' : '') . h(snip($e['message'], 80)) . '</div></td>'
            . '<td class="nowrap" data-label="Needed">' . ($e['date'] !== '' ? h(strtotime($e['date']) ? date('M j, Y', strtotime($e['date'])) : $e['date']) . ($soon ? '<div class="cell-sub">' . badge('Soon', 'warn') . '</div>' : '') : '<span class="muted">&ndash;</span>') . '</td>'
            . '<td class="nowrap" data-label="Received">' . h(fmt_when($e['ts'])) . '<div class="cell-sub">' . h(fmt_ago($e['ts'])) . '</div></td>'
            . '<td data-label="Status">' . status_badge(ENQUIRY_STATUSES, $e['status']) . '</td></tr>';
    }

    $body = page_head('Cake enquiries', 'Requests sent from the <a href="' . h(SITE_BASE) . 'contact-us/" target="_blank" rel="noopener">contact page</a> and the website\'s Order Request pop-up: custom cakes, weddings and events. A copy is also emailed to ' . h(notify_email()) . '.',
            btn_link('Download spreadsheet', admin_url('enquiries', $filters + ['csv' => 1]), 'sm', 'download'))
        . '<div class="toolbar">' . tabs_html($tabs) . '<span class="grow"></span>' . search_form('enquiries', array_diff_key($filters, ['q' => 1]), $q, 'Search name, cake, message…') . '</div>'
        . ($rows === ''
            ? empty_state($q !== '' ? 'No matching enquiries' : 'Nothing here', 'Enquiries from the contact page appear here.', 'mail')
            : '<div class="table stack"><table class="tbl stack"><thead><tr><th>From</th><th>Cake</th><th>Needed</th><th>Received</th><th>Status</th></tr></thead><tbody>' . $rows . '</tbody></table></div>' . $pager);
    admin_render('Cake enquiries', $body, 'enquiries');
}
