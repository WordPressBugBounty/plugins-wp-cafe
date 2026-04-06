<?php

namespace WpCafe\Contracts;

/**
 * Contract for WP Database
 *
 * @package WpCafe/Contracts
 */
interface Database_Interface {

    /**
     * Begin database transaction
     *
     * @return void
     */
    public function begin_transaction(): void;

    /**
     * Commit transactions to database
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Rollback uncommited transactions
     *
     * @return void
     */
    public function roll_back(): void;

    /**
     * Delete records of specific table
     *
     * @param string $table
     * @param array $where
     *
     * @return void
     */
    public function delete( string $table, array $where ): void;

    /**
     * Batch insert records
     *
     * @param string $table
     * @param array $records
     *
     * @return void
     */
    public function insert_batch( string $table, array $records ): void;

}
