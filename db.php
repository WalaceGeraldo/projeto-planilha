<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/PostgresDB.php';

require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Spreadsheet.php';

function get_all_spreadsheets() { return Spreadsheet::getAll(); }
function find_spreadsheet_meta($id) { return Spreadsheet::find($id); }
function get_username_by_id($id) { 
    $u = User::find($id);
    return $u ? $u['username'] : 'Desconhecido';
}
function find_user_by_username($username) { return User::findByUsername($username); }
function create_user($u, $p, $r='editor') { return User::create($u, $p, $r); }
function get_users() { return User::getAll(); }
function update_user($id, $u, $p, $r) { return User::update($id, $u, $p, $r); }
function delete_user($id) { return User::delete($id); }
?>
