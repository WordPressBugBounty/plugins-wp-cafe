<?php
namespace WpCafe\Database;

/**
 * Abstract Migration class.
 *
 * All migrations should extend this class and implement the up and down methods.
 */
abstract class Migration {
    /**
     * Run the migrations.
     *
     * This method should contain the logic to create tables or modify the database schema.
     *
     * @return void
     */
    abstract public function up();

    /**
     * Reverse the migrations.
     *
     * This method should contain the logic to drop tables or rollback schema changes made in the up method.
     *
     * @return void
     */
    abstract public function down();
}
