<?php
namespace Clown;

class Validation
{
    private static $_VALIDATION_TYPES = array(
        'validates_presence_of',
        'validates_size_of',
        'validates_inclusion_of',
        'validates_exclusion_of',
        'validates_format_of',
        'validates_numericality_of',
        'validates_uniqueness_of',
        'validate'
    );

    private $_registeredValidates = array(
        'create' => array(),
        'update' => array()
    );

    private $_model;
    private $_errors;

    private function _getValidationType($validate, $types)
    {
        foreach ($types as $type) {
            if (array_key_exists($type, $validate)) {
                return $type;
            }
        }
        return null;
    }

    private function _getNumericalityType($validate)
    {
        return $this->_getValidationType(
            $validate,
            array(
                'only_integer', 'even', 'odd', 'greater',
                'greater_or_equal', 'equal', 'less', 'less_or_equal'
            )
        );
    }

    private function _getSizeType($validate)
    {
        return $this->_getValidationType(
            $validate,
            array('is', 'in', 'maximum', 'minimum')
        );
    }

    private function _translateErrorMessage()
    {
        return call_user_func_array('sprintf', func_get_args());
    }

    private function _getErrorMessage($validate, $attributeValue)
    {
        if (!empty($validate['message'])) {
            return $validate['message'];
        }

        switch ($validate['type']) {
            case 'validates_presence_of':
                return $this->_translateErrorMessage('can not be blank');
            case 'validates_size_of':
                $sizeType = $this->_getSizeType($validate);
                if ($sizeType === null) {
                    return null;
                }
                switch ($sizeType) {
                    case 'is':
                        return $this->_translateErrorMessage(
                            'should be exactly %d characters long',
                            $validate['is']
                        );
                    case 'in':
                        return $this->_translateErrorMessage(
                            'should be above %d and below %d characters long',
                            $validate['in'][0] - 1,
                            $validate['in'][1] + 1
                        );
                    case 'maximum':
                        return $this->_translateErrorMessage(
                            'should not be above %d characters long',
                            $validate['maximum']
                        );
                    case 'minimum':
                        return $this->_translateErrorMessage(
                            'should not be below %d characters long',
                            $validate['minimum']
                        );
                }
            case 'validates_inclusion_of':
                return $this->_translateErrorMessage(
                    'should be a value within %s',
                    implode(',', $validate['in'])
                );
            case 'validates_exclusion_of':
                return $this->_translateErrorMessage(
                    'should not be a value within %s',
                    implode(',', $validate['in'])
                );
            case 'validates_format_of':
                return $this->_translateErrorMessage(
                    '%s can not match the format',
                    $attributeValue
                );
            case 'validates_numericality_of':
                $numericalityType = $this->_getNumericalityType($validate);
                if ($numericalityType === null) {
                    return null;
                }
                switch ($numericalityType) {
                    case 'only_integer':
                        return $this->_translateErrorMessage(
                            '%s is not an integer',
                            $attributeValue
                        );
                    case 'even':
                        return $this->_translateErrorMessage('must be even');
                    case 'odd':
                        return $this->_translateErrorMessage('must be odd');
                    case 'greater':
                        return $this->_translateErrorMessage(
                            'must be greater than %s',
                            $validate['greater']
                        );
                    case 'greater_or_equal':
                        return $this->_translateErrorMessage(
                            'must be greater than or equal to %s',
                            $validate['greater_or_equal']
                        );
                    case 'equal':
                        return $this->_translateErrorMessage(
                            'must be equal to %s',
                            $validate['equal']
                        );
                    case 'less':
                        return $this->_translateErrorMessage(
                            'must be less than %s',
                            $validate['less']
                        );
                    case 'less_or_equal':
                        return $this->_translateErrorMessage(
                            'must be less than or equal to %s',
                            $validate['less_or_equal']
                        );
                }
            case 'validates_uniqueness_of':
                return $this->_translateErrorMessage(
                    '%s has been token',
                    $attributeValue
                );
            default:
                return null;
        }
    }

    public function addError($attribute, $message)
    {
        if (!array_key_exists($attribute, $this->_errors)) {
            $this->_errors[$attribute] = array();
        }

        array_push($this->_errors[$attribute], $message);
    }

    public function errors($attribute = null)
    {
        if ($attribute === null) {
            return $this->_errors;
        }

        if (array_key_exists($attribute, $this->_errors)) {
            return $this->_errors[$attribute];
        }

        return null;
    }

    public function validate()
    {
        $this->_errors = array();
        $type = ($this->_model->isNewRecord() ? 'create' : 'update');
        if (empty($this->_registeredValidates[$type])) {
            return true;
        }

        if ($this->_model->callback->call("before_validation_on_{$type}") === false) {
            return false;
        }

        $validates = $this->_registeredValidates[$type];
        foreach ($validates as $validate) {
            if ($validate['type'] === 'validate') {
                if (method_exists($this->_model, $validate[0])) {
                    $result = Reflection::invokeMethod($this->_model, $validate[0]);
                }
                continue;
            }
            $valid = true;
            $attribute = $validate[0];
            $value = $this->_model->$attribute;
            $valueLength = strlen($value);
            if ($valueLength <= 0 && $validate['type'] !== 'validates_presence_of') {
                continue;
            }
            switch ($validate['type']) {
                case 'validates_presence_of':
                    $valid = ($valueLength > 0);
                    break;
                case 'validates_size_of':
                    $sizeType = $this->_getSizeType($validate);
                    if ($sizeType !== null) {
                        switch ($sizeType) {
                            case 'is':
                                $valid = ($valueLength === $validate['is']);
                                break;
                            case 'in':
                                $valid = ($valueLength >= $validate['in'][0] && $valueLength <= $validate['in'][1]);
                                break;
                            case 'maximum':
                                $valid = ($valueLength <= $validate['maximum']);
                                break;
                            case 'minimum':
                                $valid = ($valueLength >= $validate['minimum']);
                                break;
                        }
                    }
                    break;
                case 'validates_inclusion_of':
                    $valid = in_array($value, $validate['in']);
                    break;
                case 'validates_exclusion_of':
                    $valid = (in_array($value, $validate['in']) === false);
                    break;
                case 'validates_format_of':
                    $valid = (preg_match($validate['with'], $value) ? true : false);
                    break;
                case 'validates_numericality_of':
                    $numericalityType = $this->_getNumericalityType($validate);
                    if ($numericalityType !== null) {
                        $value = floatval($value);
                        switch ($numericalityType) {
                            case 'only_integer':
                                $valid = (preg_match('/^-?\d+$/', $value) ? true : false);
                                break;
                            case 'even':
                                $valid = ($value % 2 === 0);
                                break;
                            case 'odd':
                                $valid = ($value % 2 === 1);
                                break;
                            case 'greater':
                                $valid = ($value > $validate['greater']);
                                break;
                            case 'greater_or_equal':
                                $valid = ($value >= $validate['greater_or_equal']);
                                break;
                            case 'equal':
                                $valid = ($value === $validate['equal']);
                                break;
                            case 'less':
                                $valid = ($value < $validate['less']);
                                break;
                            case 'less_or_equal':
                                $valid = ($value <= $validate['less_or_equal']);
                                break;
                        }
                    }
                    break;
                case 'validates_uniqueness_of':
                    $options = array(
                        'conditions' => array(
                            "`{$attribute}` = ? AND `id` != ?",
                            $value,
                            $this->_model->id
                        )
                    );
                    $valid = ($this->_model->exists($options) <= 0);
                    break;
            }

            if ($valid === true) {
                continue;
            }

            $error = $this->_getErrorMessage($validate, $value);
            if ($error === null) {
                continue;
            }

            $this->addError($attribute, $error);
        }

        $this->_model->callback->call("after_validation_on_{$type}");

        return empty($this->_errors);
    }

    public function __construct($model)
    {
        $this->_model = $model;
        $reflectionClass = Reflection::getReflectionClass($model);
        $properties = $reflectionClass->getStaticProperties();
        foreach (self::$_VALIDATION_TYPES as $type) {
            if (!empty($properties[$type])) {
                foreach ($properties[$type] as $validate) {
                    $validate['type'] = $type;
                    if (empty($validate['on']) || $validate['on'] === 'save') {
                        array_push($this->_registeredValidates['create'], $validate);
                        array_push($this->_registeredValidates['update'], $validate);
                        continue;
                    }
                    if ($validate['on'] === 'create') {
                        array_push($this->_registeredValidates['create'], $validate);
                        continue;
                    }
                    if ($validate['on'] === 'update') {
                        array_push($this->_registeredValidates['update'], $validate);
                        continue;
                    }
                }
            }
        }
    }
}