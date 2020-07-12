<?php

namespace Rhino\DataTable;

/**
 * Helper class for dealing with input data (e.g. data sent over HTTP, API responses,
 * deserialized data from storage etc). When accessing items this class automatically
 * checks that it exists and casts the value to the expected type. If it does not exist a default value is returned.
 */
class InputData implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    /**
     * @var mixed[]
     */
    private $_data;

    /**
     * @param mixed[] $_data Input data
     */
    public function __construct($_data)
    {
        $this->_data = $_data;
    }

    /**
     * Cast to boolean.
     *
     * @param string $name    The name/key of input item
     * @param bool   $default The default value if the item doesn't exist
     *
     * @return bool
     */
    public function bool($name = null, $default = false)
    {
        if (!$name) {
            if (is_array($this->_data)) {
                return $default;
            }
            return (bool) $this->_data;
        }

        list($data, $name) = $this->extractDataKey($name, $this->_data);

        $result = $this->getValue($data, $name, $default);
        if (is_scalar($result)) {
            return (bool) $result;
        }
        return $default;
    }

    /**
     * Cast to an integer.
     *
     * @param string $name    The name/key of input item
     * @param int    $default The default value if the item doesn't exist
     *
     * @return int
     */
    public function int($name = null, $default = 0)
    {
        if (!$name) {
            if (is_array($this->_data)) {
                return $default;
            }
            return (int) $this->_data;
        }

        list($data, $name) = $this->extractDataKey($name, $this->_data);

        $result = $this->getValue($data, $name, $default);
        if (is_numeric($result)) {
            return (int) $result;
        }
        return $default;
    }

    /**
     * Cast to a float.
     *
     * @param string $name    The name/key of input item
     * @param float  $default The default value if the item doesn't exist
     *
     * @return float
     */
    public function decimal($name = null, $default = 0)
    {
        if (!$name) {
            if (is_array($this->_data)) {
                return $default;
            }
            return (float) $this->_data;
        }

        list($data, $name) = $this->extractDataKey($name, $this->_data);

        $result = $this->getValue($data, $name, $default);
        if (is_numeric($result)) {
            return (float) $result;
        }
        return $default;
    }

    /**
     * Cast to an string.
     *
     * @param string $name    The name/key of input item
     * @param string $default The default value if the item doesn't exist
     *
     * @return string
     */
    public function string($name = null, $default = '')
    {
        if (!$name) {
            if (is_array($this->_data)) {
                return $default;
            }
            return (string) $this->_data;
        }

        list($data, $name) = $this->extractDataKey($name, $this->_data);

        $result = $this->getValue($data, $name, $default);

        if (is_scalar($result)) {
            return (string) $result;
        }
        return $default;
    }

    /**
     * Parse a DateTime.
     *
     * @param string $name     The name/key of input item
     * @param string $timezone The timezone to use for the result, if null the default or input is used
     * @param string $default  The default value if the item doesn't exist or is invalid
     *
     * @return \DateTimeImmutable
     */
    public function dateTime($name, $timezone = null, $default = 'now')
    {
        list($data, $name) = $this->extractDataKey($name, $this->_data);

        if ($default === null && !$this->getValue($data, $name, $default)) {
            return null;
        }
        try {
            if ($timezone) {
                return new \DateTimeImmutable($this->getValue($data, $name, $default) ?: $default, new \DateTimezone($timezone));
            } else {
                return new \DateTimeImmutable($this->getValue($data, $name, $default) ?: $default);
            }
        } catch (\Exception $exception) {
            if ($timezone) {
                return new \DateTimeImmutable($default, new \DateTimezone($timezone));
            } else {
                return new \DateTimeImmutable($default);
            }
        }
    }

    /**
     * Returns a subarray of input data.
     *
     * @param string $name    The name/key of input subarray
     * @param array  $default The default value if the item doesn't exist or is not an array
     *
     * @return \Rhino\DataTable\InputData
     */
    public function arr($name = null, array $default = [])
    {
        if (!$name) {
            if (!is_array($this->_data)) {
                return new static($default);
            }
            return new static($this->_data);
        }
        list($data, $name) = $this->extractDataKey($name, $this->_data);

        return new static($this->getValue($data, $name, $default));
    }

    /**
     * JSON decode a value from the input data.
     *
     * @param string $name    The name/key of input item
     * @param array  $default The default value if the item doesn't exist
     *
     * @return \Rhino\DataTable\InputData
     */
    public function json($name = null, $default = [])
    {
        $value = $this->string($name);
        if (!$value) {
            $value = $default;
        } else {
            $value = json_decode($value, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                $value = $default;
            }
        }
        return new static($value);
    }

    /**
     * Returns a subobject of input data.
     *
     * @param string $name    The name/key of input item
     * @param mixed  $default The default value if the item doesn't exist
     *
     * @return \Rhino\DataTable\InputData
     */
    public function object($name, array $default = null)
    {
        list($data, $name) = $this->extractDataKey($name, $this->_data);

        return $this->getValue($data, $name, $default);
    }

    /**
     * Gets the raw value (unwraps the class) of data.
     *
     * @param string $name    The name/key of input item
     * @param mixed  $default The default value if the item doesn't exist
     *
     * @return mixed
     */
    public function raw($name, $default = null)
    {
        list($data, $name) = $this->extractDataKey($name, $this->_data);

        return $this->getValue($data, $name, $default);
    }

    /**
     * Converts dot delimited notations to access sub items.
     *
     * @param string  $name The name/key of input item
     * @param mixed[] $data The default value if the item doesn't exist
     *
     * @return mixed[]
     */
    public static function extractDataKey($name, $data)
    {
        $parts = explode('.', $name);
        while (count($parts) > 1) {
            $part = array_shift($parts);
            if (is_array($data)) {
                $data = isset($data[$part]) ? $data[$part] : [];
            } elseif (is_object($data)) {
                $data = isset($data->$part) ? $data->$part : [];
            } else {
                $data = [];
            }
            $name = $parts[0];
        }
        return [
            $data,
            $name,
        ];
    }

    /**
     * @return \Rhino\DataTable\InputData
     */
    public function extract($data)
    {
        $newData = [];
        foreach ($data as $key => $type) {
            $newData[$key] = $this->$type($key);
        }
        return new static($newData);
    }

    /**
     * @return \Rhino\DataTable\InputData
     */
    public function extend(array $data)
    {
        $newData = [];
        foreach ($this->_data as $key => $type) {
            $newData[$key] = $this->_data[$key];
        }
        $newData = array_replace_recursive($newData, $data);
        return new static($newData);
    }

    /**
     * @return bool True if the input data is empty
     */
    public function isEmpty()
    {
        return empty($this->_data);
    }

    /**
     * @return bool True if the input data is an array
     */
    public function isArray()
    {
        return is_array($this->_data);
    }

    /**
     * @return mixed[]
     */
    public function getData()
    {
        if (is_scalar($this->_data) || $this->_data === null) {
            return $this->_data;
        }
        array_walk_recursive($this->_data, function (&$value) {
            if ($value instanceof static) {
                $value = $value->_data;
            }
        });
        return $this->_data;
    }

    /**
     * @return \Rhino\DataTable\InputData
     */
    public function map($callback)
    {
        $result = [];
        foreach ($this as $key => $value) {
            $result[$key->_data] = $callback($value);
        }
        return new static($result);
    }

    /**
     * @return mixed
     */
    public static function getValue($data, $name, $default)
    {
        if (is_array($data)) {
            if (!array_key_exists($name, $data)) {
                return $default;
            }
            return $data[$name];
        }
        if (is_object($data)) {
            if (!isset($data->$name)) {
                return $default;
            }
            return $data->$name;
        }
        return $default;
    }

    /**
     * @return \Rhino\DataTable\InputData
     */
    public function __get($name)
    {
        if (is_array($this->_data)) {
            return isset($this->_data[$name]) ? new static($this->_data[$name]) : new static(null);
        }
        return isset($this->_data->$name) ? new static($this->_data->$name) : new static(null);
    }

    public function __set($name, $value)
    {
        if ($value instanceof static) {
            $value = $value->_data;
        }
        if (is_array($this->_data)) {
            $this->_data[$name] = $value;
        } elseif (is_object($this->_data)) {
            $this->_data->$name = $value;
        }
    }

    public function __isset($name)
    {
        if (is_array($this->_data)) {
            return isset($this->_data[$name]);
        }
        return isset($this->_data->$name);
    }

    public function __toString()
    {
        return $this->string();
    }

    public function getIterator()
    {
        if (is_array($this->_data) || is_object($this->_data)) {
            foreach ($this->_data as $key => $value) {
                yield new static($key) => new static($value);
            }
        }
    }

    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        return isset($this->$offset);
    }

    public function count()
    {
        return count($this->_data);
    }

    public function jsonSerialize()
    {
        return $this->getData();
    }
}
