#!/usr/bin/php
<?php

/**
 * DB migration script.
 */
require_once(__DIR__ . '/../phplib/411bootstrap.php');

define('VER_FN', __DIR__ . '/../version.txt');

function ver_cmp($a, $b) {
    $ar = array_map('intval', explode('.', $a));
    $br = array_map('intval', explode('.', $b));

    // Compare each part of the version string.
    for($i = 0; $i < 3; ++$i) {
        $d = $ar[$i] - $br[$i];
        if($d != 0) {
            return $d;
        }
    }
    return 0;
}

$old_ver = VERSION;
// Load in the current version from the version file.
if(file_exists(VER_FN)) {
    $ver = trim(@file_get_contents(VER_FN));
    if($ver !== false) {
        $old_ver = $ver;
    }
}

printf("Migrating from %s to %s\n", $old_ver, VERSION);

/**
 * Migration logic
 */

if(ver_cmp($old_ver, '1.0.1') < 0) {
    // Add indices to speed up common queries.
    FOO\DB::query('CREATE INDEX `jobs_target_date_idx` ON `jobs`(`target_date`)');
    FOO\DB::query('CREATE INDEX `type_target_id_site_id_archived_idx` ON `jobs`(type, target_id, site_id, archived)');
    FOO\DB::query('CREATE INDEX `jobs_type_target_id_site_id_archived_idx` ON `jobs`(type, target_id, site_id, archived)');
}

if(ver_cmp($old_ver, '1.1.0') < 0) {
    FOO\DB::query('ALTER TABLE `users` ADD COLUMN `api_key` VARCHAR(255) NOT NULL DEFAULT ""');
    $user_ids = FOO\DB::query('SELECT user_id FROM users', [], FOO\DB::COL);
    foreach($user_ids as $user_id) {
        FOO\DB::query('UPDATE `users` SET `api_key`=? WHERE `user_id`=?', [FOO\Random::base64_bytes(FOO\User::API_KEY_LEN), $user_id]);
    }
    FOO\DB::query('CREATE UNIQUE INDEX `users_site_id_api_key_idx` ON `users`(`site_id`, `api_key`);');
}

/**
 * Migration logic
 */

file_put_contents(VER_FN, VERSION);

print "Migration complete!\n";
