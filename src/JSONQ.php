<?php

namespace SoilPHP\Tools;

class JSONQ
{
    private $link;
    private $raw = [];
    private $commands = [
        'select' => '',
        'from' => '',
        'where' => [],
        'update' => [],
        'insert' => [],
//        'delete' => '',
        'has' => [],
        'join' => '',
    ];
    private $data = [];
    protected $exec = false;

    /**
     * JSONQ constructor.
     * @param string $path
     * @throws \Exception
     */
    public function __construct(string $path)
    {
        if(!file_get_contents($path)) {
            throw new \Exception('JSON file not found');
        }
        else {
            $raw = json_decode(file_get_contents($path), true);
            if($raw === false)
                throw new \Exception('Loaded file is not JSON');
            else {
                $this->link = $path;
                $this->raw = $raw;
            }
        }
    }

    /**
     * @param string $fields
     * @return $this
     */
    public function select(string $fields = '')
    {
        if($this->commands['select'] === '' && !is_bool($fields))
            $this->commands['select'] = $fields;
        else
            $this->commands['select'] .= $fields;
        return $this;
    }

    /**
     * @param string $fields
     * @return $this
     */
    public function from(string $fields = '')
    {
        if($this->commands['from'] === '' && !is_bool($fields))
            $this->commands['from'] = $fields;
        else
            $this->commands['from'] .= $fields;
        return $this;
    }

    /**
     * @param string $field
     * @param $needle
     * @return $this
     */
    public function has(string $field, $needle)
    {
        $this->commands['has'][] = ['field' => $field, 'needle' => $needle];
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @param string $sign
     * @return $this
     */
    public function where($key, $value, $sign = '=')
    {
        if(!in_array($sign, ['<', '>', '=', '<=', '>=']))
            $sign = '=';

        $this->commands['where'][] = ['key' => $key, 'sign' => $sign, 'value' => $value];
        return $this;
    }

    /**
     * @return array|mixed
     */
    public function getOriginal()
    {
        return $this->raw;
    }

    /**
     * @return array|bool|mixed
     */
    public function getAll()
    {
        return $this->runQuery();
    }

    /**
     * @return array
     */
    public function getFirst()
    {
        return $this->getAll()[0];
    }

    /**
     * @return mixed
     */
    public function getLast()
    {
        $query = $this->runQuery();
        return end($query);
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return array
     */
    public function getBySlice(int $offset, $length = null)
    {
        return array_slice($this->getAll(), $offset, $length);
    }

    /**
     * @param string $filename
     * @return bool
     * @throws \Exception
     */
    public function export(string $filename)
    {
        $data = $this->runQuery();
        $json = json_encode($data);

        if(file_put_contents($filename, $json) === false)
            throw new \Exception('This file cannot be created');
        else
            return true;
    }

    /**
     * @param array $query
     * @return $this
     */
    public function update(array $query)
    {
        $this->commands['update'] = $query;
        return $this;
    }

    /**
     * @param array $query
     * @return $this
     */
    public function insert(array $query)
    {
        $this->commands['insert'] = $query;
        return $this;
    }

    /**
     * @param string $field
     * @return $this
     */
    public function join(string $field)
    {
        $this->commands['join'] = $field;
        return $this;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function execute()
    {
        if(!is_writable($this->link))
            throw new \Exception('This file cannot be updated');

        $json = json_encode($this->runQuery());

        if(file_put_contents($this->link, $json) === false)
            return false;
        else
            return true;

    }

    /**
     * @param string $query
     * @return string
     */
    protected function createQueryArray(string $query)
    {
        $query = trim(filter_var($query, FILTER_SANITIZE_STRING));
        $string = '["'.str_replace('.', '"]["', $query).'"]';
        return $string;
    }

    /**
     * @param string $queryString
     * @param array $data
     * @return array|bool|mixed
     */
    protected function extractData(string $queryString, array $data = [])
    {
        $query = $this->createQueryArray($queryString);

        if(empty($data))
            $data = $this->raw;

        preg_match_all('/\[\"([A-z0-9_\-]+)\"\]/', $query, $matches);

        if (empty($matches)) {
            return false;
        }

        $currentData = $data;
        foreach ($matches[1] as $name) {
            $name = trim($name);
            if (array_key_exists($name, $currentData)) {
                $currentData = $currentData[$name];
            }
            else
                return false;
        }
        return $currentData;
    }

    /**
     * @param $key
     * @param $value
     * @param $sign
     * @param $data
     * @return array
     */
    public function findInArray($key, $value, $sign, $data)
    {
        $result = [];
        if(isset($data[$key])) {
            if(is_numeric($value))  {
                switch ($sign) {
                    default:
                    case '=':
                        if($data[$key] === $value)
                            $result[] = $data;
                        break;

                    case '>':
                        if($data[$key] > $value)
                            $result[] = $data;
                        break;

                    case '<':
                        if($data[$key] < $value)
                            $result[] = $data;
                        break;

                    case '<=':
                        if($data[$key] <= $value)
                            $result[] = $data;
                        break;

                    case '>=':
                        if($data[$key] >= $value)
                            $result[] = $data;
                        break;
                }
            }
            else if($data[$key] === $value)
                    $result[] = $data;
        }
        return $result;
    }

    /**
     * @return array|bool|mixed
     */
    public function query()
    {
        if(empty($this->raw))
            $data = $this->raw;
        else
            $data = $this->data;

        if($this->commands['from'] !== '') {
            $from = explode(',', $this->commands['from']);
            $fromRes = [];

            foreach ($from as $field) {
                $fromRes = array_merge_recursive($fromRes, $this->extractData($field, $data));
            }
            $data = $fromRes;
        }

        if($this->commands['select'] !== ''){
            $select = explode(',', $this->commands['select']);
            $selectRes = [];

            foreach ($select as $field) {
                if($this->commands['from'] === '' || $this->exec === true && count($data) > 1) {
//                    $mergeRes = [];
                    foreach ($data as $item) {
                        $selectRes[$field][] = $this->extractData($field, $item);
                    }
                }
                else
                    $selectRes[$field] = $this->extractData($field, $data);
            }

            $data = $selectRes;
            $this->commands['select'] = '';
            $this->commands['from'] = '';
        }

        if(!empty($this->commands['join']))
            $data = array_merge($data, $this->extractData($this->commands['join'], $this->raw));

        if(!empty($this->commands['where']) && is_array($this->commands['where'])) {
            $result = [];
            foreach ($this->commands['where'] as $where) {
                $keys = explode('.', $where['key']);
                $key = array_pop($keys);
                foreach($data as $wData) {
                    if($this->extractData($where['key'], $wData) !== false)                     {
                        $whereData = $this->extractData($where['key'], $wData);
                        if(!is_array($whereData))
                            $whereData = $this->extractData(implode('.', $keys), $wData);
                    }
                    else
                        $whereData = $wData;

                    $result = array_merge($result, $this->findInArray($key,  $where['value'], $where['sign'], $whereData));
                }
            }

            $data = $result;
        }

        if(!empty($this->commands['has']) && is_array($this->commands['has'])) {
            $result = [];
            foreach ($this->commands['has'] as $has) {
                $fields = explode('.', $has['field']);
                $field = array_pop($fields);

                foreach ($data as $hData) {
                    if($this->extractData($has['field'], $hData) !== false) {
                        $hasData = $this->extractData($has['field'], $hData);
                        if(!is_array($hasData))
                            $hasData = $this->extractData(implode('.', $fields), $hData);
                    }
                    else
                        $hasData = $hData;

                    if(is_array($hasData[$field]) && in_array($has['needle'], $hasData[$field]) || !is_array($hasData[$field]) &&  strpos($hasData[$field], $has['needle'])) {
                        $result = array_merge($result, [$hasData]);
                    }
                }
            }
            $data = $result;
        }

        if(!empty($this->commands['update']) && is_array($this->commands['update'])) {
            foreach ($this->commands['update'] as $key => $value) {
//                $key = (is_numeric($key) ? (float) $key : filter_var($key, FILTER_SANITIZE_STRING));
                $value = (is_numeric($value) ? (float) $value : filter_var($value, FILTER_SANITIZE_STRING));

                if(!isset($data[$key]))
                    continue;

                $data[$key] = $value;
            }
        }

        if(!empty($this->commands['insert']) && is_array($this->commands['insert'])) {
            foreach ($this->commands['insert'] as $key => $value) {
//                $key = (is_numeric($key) ? (float) $key : filter_var($key, FILTER_SANITIZE_STRING));
                $value = (is_numeric($value) ? (float) $value : filter_var($value, FILTER_SANITIZE_STRING));

                if(isset($data[$key]))
                    continue;

                $data[$key] = $value;
            }
        }

        $this->exec = true;
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    protected function runQuery()
    {
        $this->query();
        $data = $this->data;
        $this->data = $this->raw;
        $this->exec = false;

        return $data;
    }

}