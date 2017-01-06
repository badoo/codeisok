<?php
namespace GitPHP;

class Db_Result
{
    const NO_MORE_ROWS = false;

    protected $result;

    public function __construct($result)
    {
        $this->result = $result;
    }

    public function freeResult()
    {
        mysqli_free_result($this->result);
    }

    public function fetchAssoc()
    {
        $result = mysqli_fetch_assoc($this->result);
        return $result === null ? self::NO_MORE_ROWS : $result;
    }

    public function fetchRow()
    {
        $result = mysqli_fetch_row($this->result);
        return $result === null ? self::NO_MORE_ROWS : $result;
    }

    public function getFieldName($num)
    {
        $field_data = mysqli_fetch_field_direct($this->result, $num);
        if ($field_data) {
            return $field_data->name;
        }
        return false;
    }

    public function getRowsNum()
    {
        return mysqli_num_rows($this->result);
    }

    public function getFieldsNum()
    {
        return mysqli_num_fields($this->result);
    }

    public function seekData($row_number)
    {
        mysqli_data_seek($this->result, $row_number);
    }

    public static function isResultOk($result)
    {
        return $result instanceof \mysqli_result;
    }
}
