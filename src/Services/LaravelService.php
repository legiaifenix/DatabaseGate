<?php
namespace LegiaiFenix\Services;


use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Logging\Log;

class LaravelService
{

    /**
     * @var Connection
     */
    protected $db;

    protected $events = [];

    public function __construct(DatabaseManager $db, Log $log)
    {
        $this->db = $db;
        $this->log = $log;

        $this->events = [
            'select' => function($targetTable, Array $conditions) {
                return $this->handleSelectsArray($targetTable, $conditions);
            },
            'update' => function($targetTable, Array $conditions) {
                return $this->updateFacade($targetTable, $conditions);
            },
            'increments' => function($targetTable, Array $conditions) {
                return $this->incrementFacade($targetTable, $conditions);
            },
            'insert' => function($targetTable, Array $conditions) {
                return $this->insertFacade($targetTable, $conditions);
            }
        ];
    }


    /**
     * This is the starting point for the laravel facade for query builders
     * @param $targetTable
     * @param array $conditions
     * @param bool $debug
     */
    public function laravelFacade($targetTable, Array $conditions, $debug = false)
    {
        if( array_key_exists('select', $conditions) ) {
            return $this->selectFacade($targetTable, $conditions, $debug);
        } else if( array_key_exists('update', $conditions) ) {

            return $this->updateFacade($targetTable, $conditions);
        } else if( array_key_exists('increments', $conditions) || array_key_exists('decrements', $conditions) ) {

            return $this->incrementFacade($targetTable, $conditions);
        } else if( array_key_exists('insert', $conditions) ) {

            return $this->insertFacade($targetTable, $conditions);
        } else if( array_key_exists('delete', $conditions) ) {
            return $this->deleteFacade($targetTable, $conditions);
        }

        return "false";
    }

    /* SELECT LOGIC */

    private function selectFacade( $targetTable, Array $conditions, $debug = false )
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

            if( $debug ){
                return $query->toSql();
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
                if( count($value) > 1 ) {
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


    /* update logic block */

    private function updateFacade($targetTable, Array $conditions, $debug = false)
    {
        if( is_array( $conditions ) && is_array($conditions['update']) ){
            $query = $this->db->table( strtolower($targetTable) );

            if( array_key_exists('where', $conditions) ) {
                $query = $this->handlesWheres($query, $conditions);
            }

            if( array_key_exists('update', $conditions) ) {
                $this->handlesUpdate($query, $conditions);
            }

        }

    }


    private function handlesUpdate( $query, Array $conditions )
    {
        if( is_array($conditions['update']) ){
            $query->update( $conditions['update'] );
        }
    }


    /* INCREMENT FACADE */

    private function incrementFacade($targetTable, Array $conditions)
    {
        if( is_array($conditions) ) {
            $query = $this->db->table( strtolower($targetTable) );

            if( array_key_exists('increment', $conditions) ){
                $query->increments( $conditions['increment'][0], $conditions['increment'][1] );
            } else if( array_key_exists('decrements', $conditions) ){
                $query->decrement( $conditions['increment'][0], $conditions['increment'][1] );
            }
        }
    }

    /* INSERT FACADE */

    public function insertFacade( $targetTable, Array $conditions )
    {
        if( is_array($conditions) ) {

            return $this->db->table( strtolower($targetTable) )
                ->insertGetId($conditions['insert']);

        }
    }

    private function deleteFacade( $targetTable, Array $conditions )
    {
        if( is_array($conditions) ) {
            $query = $this->db->table($targetTable);
            $query = $this->handlesWheres($query, $conditions);
            $query->delete();
            return true;
        }
        return false;
    }

}