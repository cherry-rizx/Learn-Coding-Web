<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAccountTables extends Migration
{
    public function up()
    {
        // Kolom tambahan di tabel users milik Shield
        $this->forge->addColumn('users', [
            'is_guest'         => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
            'tutorial_seen'    => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
            'total_stars'      => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
            'stars_updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        // Index untuk query leaderboard
        $this->db->query('CREATE INDEX idx_users_leaderboard ON ' . $this->db->prefixTable('users')
            . ' (is_guest, total_stars, stars_updated_at)');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'provider'     => ['type' => 'VARCHAR', 'constraint' => 20],
            'provider_uid' => ['type' => 'VARCHAR', 'constraint' => 191],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['provider', 'provider_uid']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_social_accounts');
    }

    public function down()
    {
        $this->forge->dropTable('user_social_accounts', true);
        $this->forge->dropColumn('users', ['is_guest', 'tutorial_seen', 'total_stars', 'stars_updated_at']);
    }
}
