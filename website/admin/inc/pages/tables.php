<?php
/**
 * Tables & QR codes: the tables guests order from (private/tables.json), each with a
 * printable QR code, plus the table-ordering settings (data/menu.json "dineIn").
 *
 * Each table's QR code opens /dine-in/?t=CODE. The code is random, so a table can't be
 * ordered for by guessing; "Replace QR code" makes a new one (reprint that table's card).
 */
if (!defined('ADMIN')) { http_response_code(403); exit('Forbidden'); }

require_once __DIR__ . '/../../../inc/qr.php';

/** The site's address as seen from here (QR codes point to it: the live domain once launched). */
function tables_origin(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = preg_replace('/[^A-Za-z0-9.:\-\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
    return ($https ? 'https://' : 'http://') . $host;
}

function table_link(array $t, bool $tablet = false): string
{
    return tables_origin() . SITE_BASE . 'dine-in/?t=' . $t['code'] . ($tablet ? '&tablet=1' : '');
}

function tables_save(array $tables): bool
{
    return json_write(private_dir() . '/tables.json', array_values($tables));
}

function admin_page_tables(): void
{
    if (is_post()) admin_tables_post();
    $tables = dinein_tables();
    $s = dinein_settings();
    [$open, $why] = dinein_open();

    // Settings.
    $settings = '<div id="settings">' . card('Table ordering', form_open(admin_url('tables')) . '<input type="hidden" name="op" value="settings">'
        . '<div class="toggle-row"><label class="switch"><input type="checkbox" name="on" value="1"' . ($s['on'] ? ' checked' : '') . ' aria-label="Table ordering on"><span></span></label>'
        . '<div><b>' . ($s['on'] ? 'Table ordering is on' : 'Table ordering is off') . '</b><p class="muted small">'
        . ($open ? 'Guests can order now.' : h($why)) . ' Orders are taken during store hours (' . h(date('g:i a', strtotime(SITE['opens']))) . ' – ' . h(date('g:i a', strtotime(SITE['closes']))) . ', set in Store settings).</p></div></div>'
        . '<div class="form-grid">'
        . '<div class="field wide"><span>When an order is ready</span>'
        . '<label class="check"><input type="radio" name="serve" value="table"' . ($s['serve'] === 'table' ? ' checked' : '') . '> We bring it to the table</label>'
        . '<label class="check"><input type="radio" name="serve" value="counter"' . ($s['serve'] === 'counter' ? ' checked' : '') . '> The guest collects it at the counter when their number shows</label></div>'
        . '<label class="field sm"><span>Show as late after</span><span class="mins"><input name="lateMins" value="' . (int) $s['lateMins'] . '" inputmode="numeric"> min</span></label>'
        . '<label class="field wide"><span>Payment note for guests</span><input name="payNote" value="' . h($s['payNote']) . '" maxlength="160"></label>'
        . '<label class="check"><input type="checkbox" name="taxAll" value="1"' . ($s['taxAll'] ? ' checked' : '') . '> Add HST to everything ordered at a table</label>'
        . '</div><p class="muted small">In Ontario, food eaten on the premises is usually taxable even when the same item to take away isn\'t; please confirm with your accountant. Categories can be left out of table ordering in Menu &amp; prices.</p>'
        . '<div class="bar">' . btn_submit('Save table ordering', 'primary', '', '', 'check') . '</div></form>') . '</div>';

    // The tables.
    $rows = '';
    foreach ($tables as $i => $t) {
        $id = (int) $t['id'];
        $rows .= '<tr' . (empty($t['active']) ? ' class="is-off"' : '') . '>'
            . '<td data-label="On"><label class="switch"><input type="checkbox" name="active[' . $id . ']" value="1"' . (!empty($t['active']) ? ' checked' : '') . ' aria-label="' . h($t['name']) . ' takes orders"><span></span></label><input type="hidden" name="ids[]" value="' . $id . '"></td>'
            . '<td data-label="Name"><input name="name[' . $id . ']" value="' . h($t['name']) . '" maxlength="30" aria-label="Table name"></td>'
            . '<td data-label="Links"><a href="' . h(table_link($t)) . '" target="_blank" rel="noopener">Phone page</a> &middot; <a href="' . h(table_link($t, true)) . '" target="_blank" rel="noopener">Tablet page</a></td>'
            . '<td data-label=""><label class="check"><input type="checkbox" name="newcode[' . $id . ']" value="1"> Replace QR code</label></td>'
            . '<td data-label=""><label class="check danger"><input type="checkbox" name="del[' . $id . ']" value="1"> Remove</label></td></tr>';
    }
    $list = card('Tables', ($rows === ''
            ? '<p class="muted">No tables yet. Add them below; each one gets its own QR code.</p>'
            : form_open(admin_url('tables')) . '<input type="hidden" name="op" value="save">'
                . '<div class="table flush stack"><table class="tbl ftable compact stack"><thead><tr><th>On</th><th>Name</th><th>Links</th><th></th><th></th></tr></thead><tbody>' . $rows . '</tbody></table></div>'
                . '<div class="bar">' . btn_submit('Save tables', 'primary', '', '', 'check', 'Save? Tables marked "Replace QR code" need their new card printed, and removed tables stop working.') . '</div></form>')
        . '<h3 class="sub-h">Add tables</h3>' . form_open(admin_url('tables')) . '<input type="hidden" name="op" value="add"><div class="form-grid">'
        . '<label class="field sm"><span>How many</span><input name="count" value="' . ($tables ? 1 : 10) . '" inputmode="numeric"></label>'
        . '<label class="field"><span>Name <small class="muted">(blank = Table 1, Table 2…)</small></span><input name="label" maxlength="30" placeholder="e.g. Patio"></label>'
        . '</div><div class="bar">' . btn_submit('Add', 'primary', '', '', 'plus') . '</div></form>');

    // Printable QR cards.
    $cards = '';
    foreach ($tables as $t) {
        if (empty($t['active'])) continue;
        $cards .= '<figure class="qr-card"><img class="qr-logo" src="' . h(SITE_BASE) . 'images/logo.png" alt="La Sem">'
            . '<figcaption><b>' . h($t['name']) . '</b><span>Scan to order from your table</span></figcaption>'
            . qr_svg(table_link($t), 'QR code for ' . $t['name'])
            . '<small>Pay at the counter · ' . h(SITE['phone']) . '</small></figure>';
    }
    $print = '<div id="qr">' . card('QR codes to print', ($cards === ''
            ? '<p class="muted">Add a table to see its QR code.</p>'
            : '<p class="muted small no-print">Print these, cut them out and put one on each table (a stand or sticker). Guests scan with their phone camera; no app needed. '
                . 'The codes open <b>' . h(tables_origin() . SITE_BASE) . 'dine-in/</b>' . (preg_match('/localhost|127\.0\.0\.1/', tables_origin()) ? ', which phones can\'t reach: print the final codes from the live site\'s admin.' : '.') . '</p>'
                . '<div class="qr-sheet">' . $cards . '</div>'),
        '<button class="btn sm no-print" type="button" data-print>' . icon('print', 16) . '<span>Print QR codes</span></button>', 'qr-print') . '</div>';

    $help = card('Screens', '<ul class="linklist">'
        . '<li><a href="' . h(SITE_BASE) . 'kds/" target="_blank" rel="noopener">Kitchen display</a><span>Table orders show with their number and table</span></li>'
        . '<li><a href="' . h(SITE_BASE) . 'dine-in/board/" target="_blank" rel="noopener">"Now serving" board</a><span>For a TV at the counter: numbers being prepared and ready</span></li>'
        . '</ul><p class="muted small">Table tablets: open a table\'s <b>Tablet page</b> on the tablet and keep it there (full screen or guided access). After each order it shows the number and a QR code to follow it on a phone, then resets for the next guest.</p>', '', 'no-print');

    $body = page_head('Tables & QR codes', 'Guests scan the QR code on their table (or use a table tablet), order from the menu and get a number to follow. Orders go straight to the kitchen display.',
            btn_link('Print QR codes', admin_url('tables') . '#qr', 'sm', 'print'))
        . '<div class="split"><div class="stack">' . $list . $print . '</div><div class="stack no-print">' . $settings . $help . '</div></div>';
    admin_render('Tables & QR codes', $body, 'tables');
}

function admin_tables_post(): void
{
    require_csrf();
    $op = (string) ($_POST['op'] ?? '');
    $tables = dinein_tables();

    if ($op === 'settings') {
        $menu = data_read('menu');
        $menu['dineIn'] = [
            'on'       => !empty($_POST['on']),
            'serve'    => ($_POST['serve'] ?? '') === 'counter' ? 'counter' : 'table',
            'taxAll'   => !empty($_POST['taxAll']),
            'payNote'  => trim(post_str('payNote', 160)),
            'lateMins' => max(3, min(120, (int) ($_POST['lateMins'] ?? 15))),
        ];
        if (data_save('menu', $menu)) {
            admin_log('tables.settings', ($menu['dineIn']['on'] ? 'on' : 'off') . ', ' . $menu['dineIn']['serve']);
            flash('Table ordering settings saved.');
        } else {
            flash('The settings couldn\'t be saved.', 'err');
        }
        redirect(admin_url('tables') . '#settings');
    }

    if ($op === 'add') {
        $count = max(1, min(50, (int) ($_POST['count'] ?? 1)));
        $label = trim(post_str('label', 30));
        $nextId = $tables ? max(array_column($tables, 'id')) + 1 : 1;
        $used = array_column($tables, 'code');
        // Number new tables after the highest "Table N" so far.
        $n = 0;
        foreach ($tables as $t) if (preg_match('/^Table (\d+)$/', $t['name'], $m)) $n = max($n, (int) $m[1]);
        for ($i = 0; $i < $count; $i++) {
            do { $code = dinein_new_code(); } while (in_array($code, $used, true));
            $used[] = $code;
            $name = $label === '' ? 'Table ' . (++$n) : ($count > 1 ? $label . ' ' . ($i + 1) : $label);
            $tables[] = ['id' => $nextId++, 'name' => $name, 'code' => $code, 'active' => true];
        }
        if (tables_save($tables)) {
            admin_log('tables.add', plural($count, 'table'));
            flash('Added ' . plural($count, 'table') . '. Their QR codes are ready to print below.');
        } else {
            flash('The tables couldn\'t be saved. Check that website/private can be written to.', 'err');
        }
        redirect(admin_url('tables'));
    }

    if ($op === 'save') {
        $out = [];
        $replaced = $removed = 0;
        $used = array_column($tables, 'code');
        foreach ($tables as $t) {
            $id = (int) $t['id'];
            if (!in_array((string) $id, array_map('strval', (array) ($_POST['ids'] ?? [])), true)) { $out[] = $t; continue; }
            if (!empty($_POST['del'][$id])) { $removed++; admin_log('tables.delete', $t['name']); continue; }
            $name = trim(mb_substr((string) ($_POST['name'][$id] ?? ''), 0, 30));
            if ($name !== '') $t['name'] = $name;
            $t['active'] = !empty($_POST['active'][$id]);
            if (!empty($_POST['newcode'][$id])) {
                do { $code = dinein_new_code(); } while (in_array($code, $used, true));
                $used[] = $code;
                $t['code'] = $code;
                $replaced++;
                admin_log('tables.code', $t['name']);
            }
            $out[] = $t;
        }
        if (tables_save($out)) {
            admin_log('tables.save', plural(count($out), 'table'));
            flash('Tables saved.' . ($replaced ? ' Print the new QR code for ' . plural($replaced, 'table') . '; the old one no longer works.' : '') . ($removed ? ' ' . plural($removed, 'table') . ' removed.' : ''));
        } else {
            flash('The tables couldn\'t be saved.', 'err');
        }
        redirect(admin_url('tables'));
    }
    redirect(admin_url('tables'));
}
