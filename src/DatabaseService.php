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

class DatabaseService
{
    protected $eventsKeys = ['select', 'join', 'leftJoin', 'where', 'orWhere', 'whereBetween', 'group', 'having', 'sum', 'count', 'paginate'];

    protected $activeVal = 1;
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var Log
     */
    protected $log;

    public function __construct(DatabaseManager $db, Log $log)
    {
        $this->db = $db;
        $this->log = $log;
    }

    /**
     * This functions fetches all information from a table or specific if a array is provided.
     * If you wish all just pass empty array as sec arg. If you wish to narrow it down, pass array with specifications.
     * E.g:. ['client.id', '=', 'product_id'] -> creates a where clause. You cna narrow down as much as needed
     * @param $targetTable
     * @param array $conditions
     * @return mixed
     */
    public function getEntriesOfTableWithConditions($targetTable, Array $conditions)
    {
        if( is_array($conditions) ) {
            $query = $this->db->table( strtolower($targetTable) );

            if( array_key_exists('select', $conditions) ){
                $query = $this->handleSelectsArray($query, $conditions);
            }


            if( array_key_exists('where', $conditions) || array_key_exists('orWhere', $conditions) ) {
                $query = $this->handlesWheres($query, $conditions);
            }

            if( array_key_exists('whereBetween', $conditions) ){
                $query = $this->handlesWhereBetween($query, $conditions);
            }

            if( array_key_exists('join', $conditions) ){
                $query = $this->handlesJoins($query, $conditions);
            }

            if( array_key_exists('group', $conditions) ){
                $query = $query->groupBy($conditions['group']);
            }

            if( array_key_exists('having', $conditions) ){
                $query = $this->handlesHaving($query, $conditions);
            }

            if( array_key_exists('order', $conditions) ){
                if( count( $conditions['order'] ) == 2 ){
                    $query = $query->orderBy($conditions['order'][0], $conditions['order'][1]);
                } else if ( count($conditions['order']) == 1 ) {
                    $query = $query->orderBy($conditions['order'][0], 'ASC');
                } else {
                    $query = $query->orderByRaw( $conditions['order'][0].' <> '.$conditions['order'][1].', '.$conditions['order'][0]." ".$conditions['order'][2] );
                }
            }


            if( array_key_exists("count", $conditions) ){
                return $query->count();
            } else if( array_key_exists("sum", $conditions) ) {
                return $query->sum($conditions['sum']);
            }

            if( array_key_exists('paginate', $conditions) ) {
                return $this->handlesPagination($query, $conditions);
            } else if( array_key_exists('first', $conditions) ) {
                return $query->first();
            } else {
                return $query->get();
            }

        } else {
            $this->log->info("Error 1: Error found in getEntriesOfTableWithConditions Class DatabaseService: conditions not an array");
            return false;
        }

    }

    private function handlesWhereBetween($query, $conditions)
    {
        if( is_array($conditions['whereBetween']) && count($conditions['whereBetween']) > 0 ) {
            foreach ($conditions['whereBetween'] as $key => $value) {
                if( count($value) > 2 ) {
                    $query = $query->whereBetween(
                        $key,
                        [
                            $value[0],
                            $value[1]
                        ]
                    );
                }
            }
        }

        return $query;
    }

    private function handlesHaving($query, $conditions)
    {
        if( array_key_exists('having', $conditions) ) {
            foreach ($conditions['having'] as $key => $condition ) {
                $query->havingRaw($condition[0]);
            }
        }

        return $query;
    }

    private function handlesPagination($query, $conditions)
    {
        if( is_array($conditions['paginate']) && count($conditions['paginate']) > 1 ){
            return $query->skip( ($conditions['paginate'][0] * $conditions['paginate'][1]) )->take($conditions['paginate'][0])->get();
        } else {
            return $query->paginate($conditions['paginate']);
        }

    }

    /**
     * Prepares the Wheres as specific code block section
     * @param $query
     * @param $conditions
     * @return mixed
     */
    private function handlesWheres($query, $conditions) {

        if( array_key_exists("where", $conditions) ) {

            foreach ($conditions['where'] as $condition)
            {
                if( count($condition) > 2 ) {
                    $query = $query->where(
                        strtolower($condition[0]),
                        strtolower($condition[1]),
                        strtolower($condition[2])
                    );
                }

            }

        }

        if( array_key_exists("orWhere", $conditions) ) {
            foreach ($conditions['orWhere'] as $condition) {

                if( $condition == reset($conditions['orWhere']) ) {
                    $query->where(
                        strtolower($condition[0]),
                        strtolower($condition[1]),
                        strtolower($condition[2])
                    );
                } else {
                    $query->orWhere( function($query) use ($condition) {
                        if( count($condition) > 2 ) {
                            $query = $query->where(
                                strtolower($condition[0]),
                                strtolower($condition[1]),
                                strtolower($condition[2])
                            );
                        }
                    }
                    );
                }
             }
        }

        return $query;
    }

    /**
     * Specific code block to prepare the selects
     * @param $query
     * @param $selects
     * @return mixed
     */
    private function handleSelectsArray($query, $selects)
    {
        $selectQuery = "";


        foreach ($selects['select'] as $key => $select) {
            $lastkey = count($selects['select']) - 1;
            if( $key == $lastkey ){
                //dd($key);
                if( count($select) == 1 && is_array($select) ) {
                    $selectQuery .= $select[0];
                } else if( count($select) > 1 ) {
                    $selectQuery .= $this->db->raw("$select[0] as $select[1]");
                }
            } else {
                if( count($select) == 1 && is_array($select) ) {
                    $selectQuery .= $select[0].",";
                } else if( count($select) > 1 ) {
                    $selectQuery .= $this->db->raw("$select[0] as $select[1]").",";
                }
            }
        }

        if( is_array($selects) && count($selects) > 0 ) {
            return $query->select( $this->db->raw($selectQuery) );
        }
        return $query;
    }

    /**
     * This is a specific code block to prepare the join queries
     * @param $query
     * @param $joins
     * @return mixed
     */
    private function handlesJoins($query, $joins)
    {
        if( is_array($joins['join']) && count($joins['join']) > 0 ) {
            foreach ($joins['join'] as $key => $value) {
                if( count($value) > 2 ) {
                    $query = $query->join(
                        $key,
                        $value[0],
                        $value[1],
                        $value[2]
                    );
                }
            }
        }


        return $query;
    }


}