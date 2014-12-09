<?php
namespace Clown;

class Validation
{
    private static $_VALIDATION_TYPES = array(
        'validatePresenceOf',
        'validateSizeOf',
        'validateInclusionOf',
        'validateExclusionOf',
        'validateFormatOf',
        'validateNumericalityOf',
        'validateUniquenessOf',
        'validate'
    );

    private $_registeredValidates = array(
        'insert' => array(),
        'update' => array()
    );

    private $_model;

    public $errors;

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
                'onlyInteger', 'even', 'odd', 'greater',
                'greaterOrEqual', 'equal', 'less', 'lessOrEqual'
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

    private function _translateError()
    {
        return call_user_func_array('sprintf', func_get_args());
    }


    private function _getError($validate, $attributeValue)
    {
        if (!empty($validate['message'])) {
            return $validate['message'];
        }

        switch ($validate['type']) {
            case 'validatePresenceOf':
                return $this->_translateError('can not be blank');
            case 'validateSizeOf':
                $sizeType = $this->_getSizeType($validate);
                if ($sizeType === null) {
                    return null;
                }
                switch ($sizeType) {
                    case 'is':
                        return $this->_translateError(
                            'should be exactly %d characters long',
                            $validate['is']
                        );
                    case 'in':
                        return $this->_translateError(
                            'should be above %d and below %d characters long',
                            $validate['in'][0] - 1,
                            $validate['in'][1] + 1
                        );
                    case 'maximum':
                        return $this->_translateError(
                            'should not be above %d characters long',
                            $validate['maximum']
                        );
                    case 'minimum':
                        return $this->_translateError(
                            'should not be below %d characters long',
                            $validate['minimum']
                        );
                }
            case 'validateInclusionOf':
                return $this->_translateError(
                    'should be a value within %s',
                    implode(',', $validate['in'])
                );
            case 'validateExclusionOf':
                return $this->_translateError(
                    'should not be a value within %s',
                    implode(',', $validate['in'])
                );
            case 'validateFormatOf':
                return $this->_translateError(
                    '%s can not match the format',
                    $attributeValue
                );
            case 'validateNumericalityOf':
                $numericalityType = $this->_getNumericalityType($validate);
                if ($numericalityType === null) {
                    return null;
                }
                switch ($numericalityType) {
                    case 'onlyInteger':
                        return $this->_translateError(
                            '%s is not an integer',
                            $attributeValue
                        );
                    case 'even':
                        return $this->_translateError('must be even');
                    case 'odd':
                        return $this->_translateError('must be odd');
                    case 'greater':
                        return $this->_translateError(
                            'must be greater than %s',
                            $validate['greater']
                        );
                    case 'greaterOrEqual':
                        return $this->_translateError(
                            'must be greater than or equal to %s',
                            $validate['greaterOrEqual']
                        );
                    case 'equal':
                        return $this->_translateError(
                            'must be equal to %s',
                            $validate['equal']
                        );
                    case 'less':
                        return $this->_translateError(
                            'must be less than %s',
                            $validate['less']
                        );
                    case 'lessOrEqual':
                        return $this->_translateError(
                            'must be less than or equal to %s',
                            $validate['lessOrEqual']
                        );
                }
            case 'validateUniquenessOf':
                return $this->_translateError(
                    '%s has been token',
                    $attributeValue
                );
            default:
                return null;
        }
    }

    public function validate()
    {
        $this->errors = array();
        $type = ($this->_model->isNewRecord() ? 'insert' : 'update');
        if (empty($this->_registeredValidates[$type])) {
            return true;
        }

        $validates = $this->_registeredValidates[$type];
        foreach ($validates as $validate) {
            if ($validate['type'] === 'validate') {
                if (method_exists($this->_model, $validate[0])) {
                    $this->_model->$validate[0]();
                }

                continue;
            }

            $valid = true;
            $attribute = $validate[0];
            $value = $this->_model->$attribute;
            $valueLength = strlen($value);
            if ($valueLength <= 0 && $validate['type'] !== 'validatePresenceOf') {
                continue;
            }

            switch ($validate['type']) {
                case 'validatePresenceOf':
                    $valid = ($valueLength > 0);
                    break;
                case 'validateSizeOf':
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
                case 'validateInclusionOf':
                    $valid = in_array($value, $validate['in']);
                    break;
                case 'validateExclusionOf':
                    $valid = (in_array($value, $validate['in']) === false);
                    break;
                case 'validateFormatOf':
                    $valid = (preg_match($validate['with'], $value) ? true : false);
                    break;
                case 'validateNumericalityOf':
                    $numericalityType = $this->_getNumericalityType($validate);
                    if ($numericalityType !== null) {
                        $value = floatval($value);
                        switch ($numericalityType) {
                            case 'onlyInteger':
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
                            case 'greaterOrEqual':
                                $valid = ($value >= $validate['greaterOrEqual']);
                                break;
                            case 'equal':
                                $valid = ($value === $validate['equal']);
                                break;
                            case 'less':
                                $valid = ($value < $validate['less']);
                                break;
                            case 'lessOrEqual':
                                $valid = ($value <= $validate['lessOrEqual']);
                                break;
                        }
                    }
                    break;
                case 'validateUniquenessOf':
                    $options = array(
                        'conditions' => array(
                            "`{$attribute}` = ? AND id != ?",
                            $value,
                            $this->_model->id
                        )
                    );
                    $valid = ($this->_model->count($options) <= 0);
                    break;
            }

            if ($valid === true) {
                continue;
            }

            $error = $this->_getError($validate, $value);
            if ($error === null) {
                continue;
            }

            $this->_model->addError($attribute, $error);
        }

        return empty($this->errors);
    }

    public function __construct($model)
    {
        $this->_model = $model;

        $modelName = get_class($model);
        $modelProperties = get_class_vars($modelName);
        foreach (self::$_VALIDATION_TYPES as $type) {
            if (!empty($modelProperties[$type])) {
                foreach ($modelProperties[$type] as $validate) {
                    $validate['type'] = $type;
                    if (empty($validate['on'])) {
                        array_push($this->_registeredValidates['insert'], $validate);
                        array_push($this->_registeredValidates['update'], $validate);
                        continue;
                    }

                    if ($validate['on'] === 'insert') {
                        array_push($this->_registeredValidates['insert'], $validate);
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