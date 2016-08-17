<?php
/**
 * Created by PhpStorm.
 * User: diogosanto
 * Date: 23/05/16
 * Time: 11:24
 */

namespace LegiaiFenix\DatabaseGate;

use Illuminate\Contracts\Logging\Log;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use LegiaiFenix\DatabaseGate\Services\LaravelService;

class DatabaseService
{
    protected $eventsKeys = ['select', 'join', 'leftJoin', 'where', 'orWhere', 'whereBetween', 'group', 'having', 'sum', 'count', 'paginate'];

    protected $types;

    protected $activeVal = 1;
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var Log
     */
    protected $log;

    protected $statements;

    protected $laravel;

    public function __construct(DatabaseManager $db, Log $log, LaravelService $laravel)
    {
        $this->db = $db;
        $this->log = $log;

        $this->laravel = $laravel;

        $this->statements = [
            'laravel' => function($targetTable, $conditions, $debug) {
                return $this->laravel->laravelFacade($targetTable, $conditions, $debug);
            },
            'pdo' => function($targetTable, $conditions, $debug) {
                return "pdo";
            }
        ];
    }

    /**
     * This functions fetches all information from a table or specific if a array is provided.
     * If you wish all just pass empty array as sec arg. If you wish to narrow it down, pass array with specifications.
     * E.g:. ['client.id', '=', 'product_id'] -> creates a where clause. You cna narrow down as much as needed
     * @param $targetTable
     * @param array $conditions
     * @return mixed
     */
    public function getEntriesOfTableWithConditions($targetTable, Array $conditions, $debug = false, $type = 'laravel')
    {

        if( $type == 'laravel' ){
            return $this->laravel->laravelFacade($targetTable, $conditions, $debug);
        } else if( $type == "pdo" ){
            return "not yet implemented";
        }
    }



    /* PDO Queries */

    private function pdoQueries($targetTable, $conditions)
    {
        $query = "";

        return "query";
    }


}