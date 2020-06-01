<?php

class MobiusTrader_Search
{
    protected $_client;

    protected $_query;
    protected $_condition;

    // SELECT ...
    protected $_select = array();

    // FROM ...
    protected $_from = '';

    // WHERE ...
    protected $_where = array();

    // ORDER BY ...
    protected $_order_by = null;
    protected $_order_dir = null;

    // GROUP BY ...
    protected $_group_by = null;

    // LIMIT ...
    protected $_limit = NULL;

    // OFFSET ...
    protected $_offset = NULL;

    protected $_result = NULL;

    public function __construct($client, array $columns = NULL)
    {
        $this->_client = $client;

        if (!empty($columns)) {
            // Set the initial columns
            $this->_select = $columns;
        }
    }

    public function from($source)
    {
        $this->_from = $source;

        return $this;
    }

    public function group_by($column)
    {
        $this->_group_by = $column;

        return $this;
    }

    /**
     * Start returning results after "OFFSET ..."
     *
     * @param   integer $number starting result number or NULL to reset
     * @return  $this
     */
    public function offset($number)
    {
        $this->_offset = $number;

        return $this;
    }

    public function where($column, $op, $value)
    {
        return $this->and_where($column, $op, $value);
    }

    public function and_where($column, $op, $value)
    {
        $this->_where[] = array('AND' => array($column, $op, $value));

        return $this;
    }

    public function or_where($column, $op, $value)
    {
        $this->_where[] = array('OR' => array($column, $op, $value));

        return $this;
    }


    /**
     * Alias of and_where_open()
     *
     * @return  $this
     */
    public function where_open()
    {
        return $this->and_where_open();
    }

    /**
     * Opens a new "AND WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function and_where_open()
    {
        $this->_where[] = array('AND' => '(');

        return $this;
    }

    /**
     * Opens a new "Or WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function or_where_open()
    {
        $this->_where[] = array('OR' => '(');

        return $this;
    }

    /**
     * Closes an open "WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function where_close()
    {
        return $this->and_where_close();
    }

    /**
     * Closes an open "WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function and_where_close()
    {
        $this->_where[] = array('AND' => ')');

        return $this;
    }

    /**
     * Closes an open "WHERE (...)" grouping.
     *
     * @return  $this
     */
    public function or_where_close()
    {
        $this->_where[] = array('OR' => ')');

        return $this;
    }

    /**
     * Applies sorting with "ORDER BY ..."
     *
     * @param   mixed $column column name or array($column, $alias) or object
     * @param   string $direction direction of sorting
     * @return  $this
     */
    public function order_by($column, $direction = NULL)
    {
        $this->_order_by = $column;
        $this->_order_dir = $direction;

        return $this;
    }

    /**
     * Return up to "LIMIT ..." results
     *
     * @param   integer $number maximum results to return or NULL to reset
     * @return  $this
     */
    public function limit($number)
    {
        $this->_limit = $number;

        return $this;
    }

    public function execute()
    {
        $this->compile();

        $result = $this->_client->call('Search', $this->_query);

        if ($result['status'] == MobiusTrader::STATUS_OK) {
            $this->_result = $result['data'];
        } else {
            throw new Exception('MobiusTrader search error: ' . $result['data']);
        }
        return $this;
    }

    /**
     * Compile the query and return it.
     *
     * @return  string
     */
    public function compile()
    {
        // Start a selection query
        $query = array();

        $query['Context'] = $this->_from;

        $query['Select'] = $this->_compile_select($this->_select);

        $query['Where'] = $this->_compile_where($this->_where);

        if (!empty($this->_group_by)) {
            // Add grouping
            $query['GroupBy'] = $this->_group_by;
        }

        if (!empty($this->_order_by)) {
            // Add sorting
            $query['SortBy'] = $this->_order_by;

            $query['SortDir'] = $this->_order_dir ? strtoupper($this->_order_dir) : 'ASC';
        }

        if ($this->_limit !== NULL) {
            // Add limiting
            $query['Limit'] = $this->_limit;
        }

        if ($this->_offset !== NULL) {
            // Add offsets
            $query['Offset'] = $this->_offset;
        }

        $this->_query = $query;

        return $this;
    }

    protected function _compile_select(array $fields_src)
    {
        if (empty($fields_src)) {
            return array(
                'Ticket',
                'SymbolId',
                'Comment',
                'OpenTime',
                'CloseTime',
                'TradeCmd',
                'AccountNumberId',
                'Sl',
                'Tp',
                'Volume',
                'OpenPrice',
                'ClosePrice',
                'Commission',
                'Swap',
                'Profit',
            );
        }

        $skip_fields = array();

        $fields = array();

        foreach ($fields_src as $field) {
            if (in_array($field, $skip_fields)) {
                continue;
            }

            if (is_array($field) && count($field) == 2) {
                $fields[] = array(
                    'Expr' => $field[0],
                    'As' => $field[1],
                );
            } else {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Compiles an array of conditions into partial. Used for WHERE
     * and HAVING.
     *
     * @param   array $where where statements
     * @return  array
     */
    protected function _compile_where(array $where)
    {
        $last_condition = NULL;

        $result = array();
        $item = array();

        foreach ($where as $group) {
            // Process groups of conditions
            foreach ($group as $logic => $condition) {
                $logic = strtoupper($logic);

                if ($condition === '(') {
                    if (!empty($result) && $last_condition !== '(') {
                        $result[] = $logic;
                    } elseif (!empty($item) && $last_condition !== '(') {
                        $item[] = $logic;
                    }

                    if (!empty($item)) {
                        if (empty($result)) {
                            $result = $item;
                        } else {
                            $result[] = $item;
                        }
                        $item = array();
                    }
                } elseif ($condition === ')') {
                    if (!empty($item)) {
                        $result[] = $item;
                    }
                    $item = array();
                } else {
                    if (!empty($item) && $last_condition !== '(') {
                        $item[] = $logic;
                    } elseif (!empty($result) && $last_condition !== '(') {
                        $result[] = $logic;
                    }

                    // Split the condition
                    list($column, $op, $value) = $condition;

                    $op = strtoupper($op);

                    $item[] = $column;
                    $item[] = $op;
                    $item[] = $this->_compile_condition_query($value, $op);
                }

                $last_condition = $condition;
            }
        }

        if (!empty($item)) {
            $result[] = $item;
        }

        if (empty($result)) {
            $result = array('Ticket', '>', 0);
        } elseif (count($result) == 1) {
            $result = $item;
        }

        return $result;
    }

    protected function _compile_condition_query($value, $op)
    {
        if (strpos($op, 'IN') !== false) {
            $value = (array)$value;
        }

        return $value;
    }

    private static function _expr_parse($string, $start = 0)
    {
        $result = array();
        $val = '';
        $len = strlen($string);
        for ($i = $start; $i < $len; $i++) {
            $char = $string[$i];
            if ($char == '(') {
                if (!empty($val)) {
                    $result[] = $val;
                    $val = '';
                }
                list($i, $sub_arr) = self::_expr_parse($string, $i + 1);
                $result[] = $sub_arr;
            } elseif ($char == ')') {
                if (!empty($val)) {
                    $result[] = $val;
                    $val = '';
                }
                break;
            } elseif (preg_match("/[+-\/\*]+/", $char)) {
                if (!empty($val)) {
                    $result[] = $val;
                    $val = '';
                }
                $result[] = $char;
            } elseif ($char !== ' ') {
                $val .= $char;
            }
        }
        if (!empty($val)) {
            $result[] = $val;
        }
        return $start == 0 ? $result : array($i, $result);
    }


    public static function expr($string)
    {
        return self::_expr_parse($string);
    }

    public function as_array($key = NULL, $value = NULL)
    {
        $results = array();

        if ($key === NULL && $value === NULL) {
            // Indexed rows
            foreach ($this->_result as $row) {
                $results[] = $row;
            }
        } elseif ($key === NULL) {
            // Indexed columns
            foreach ($this->_result as $row) {
                $results[] = $row[$value];
            }
        } elseif ($value === NULL) {
            foreach ($this->_result as $row) {
                $results[$row[$key]] = $row;
            }
        } else {
            foreach ($this->_result as $row) {
                $results[$row[$key]] = $row[$value];
            }
        }

        return $results;
    }

    public function get($name, $default = NULL)
    {
        $row = $this->current();

        if (isset($row[$name]))
            return $row[$name];

        return $default;
    }

    public function current()
    {
        if (isset($this->_result[0])) {
            return $this->_result[0];
        }
        return $this->_result;
    }
}