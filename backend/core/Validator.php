<?php
/**
 * 输入验证
 * PHP 7.4兼容
 */

class Validator
{
    /** @var array 收集的错误 */
    private array $errors = [];

    /**
     * 根据规则验证数据
     *
     * @param array $data  输入数据（通常是$_POST或json请求体）
     * @param array $rules ['field' => 'rule1|rule2:param', ...]
     * @return array  成功返回空数组，失败返回 ['field' => ['error1', ...]]
     */
    public function validate(array $data, array $rules): array
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $ruleList = explode('|', $ruleString);

            foreach ($ruleList as $rule) {
                $param = null;
                if (strpos($rule, ':') !== false) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                $method = 'rule' . ucfirst($rule);
                if (!method_exists($this, $method)) {
                    continue;
                }

                if (!$this->$method($field, $value, $param)) {
                    break; // 该字段遇到第一个错误时停止
                }
            }
        }

        return $this->errors;
    }

    /**
     * 必填字段
     */
    public function ruleRequired(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, $field . '不能为空');
            return false;
        }
        return true;
    }

    /**
     * 最大长度
     */
    public function ruleMaxLength(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $max = (int) $param;
        if (mb_strlen((string) $value) > $max) {
            $this->addError($field, $field . '长度不能超过' . $max . '个字符');
            return false;
        }
        return true;
    }

    /**
     * 最小长度
     */
    public function ruleMinLength(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $min = (int) $param;
        if (mb_strlen((string) $value) < $min) {
            $this->addError($field, $field . '长度不能少于' . $min . '个字符');
            return false;
        }
        return true;
    }

    /**
     * 邮箱格式
     */
    public function ruleEmail(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $field . '格式不正确');
            return false;
        }
        return true;
    }

    /**
     * 中国手机号码
     */
    public function rulePhone(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!preg_match('/^1[3-9]\d{9}$/', (string) $value)) {
            $this->addError($field, $field . '格式不正确');
            return false;
        }
        return true;
    }

    /**
     * 数字值
     */
    public function ruleNumeric(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!is_numeric($value)) {
            $this->addError($field, $field . '必须是数字');
            return false;
        }
        return true;
    }

    /**
     * 整数值
     */
    public function ruleInteger(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, $field . '必须是整数');
            return false;
        }
        return true;
    }

    /**
     * 值在允许列表中（参数中以逗号分隔）
     */
    public function ruleIn(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $allowed = $param !== null ? explode(',', $param) : [];
        if (!in_array((string) $value, $allowed, true)) {
            $this->addError($field, $field . '的值不在允许范围内');
            return false;
        }
        return true;
    }

    /**
     * 日期格式（Y-m-d 或 Y-m-d H:i:s）
     */
    public function ruleDate(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $format = $param ?: 'Y-m-d';
        $d = DateTime::createFromFormat($format, (string) $value);
        if (!$d || $d->format($format) !== (string) $value) {
            $this->addError($field, $field . '日期格式不正确，应为' . $format);
            return false;
        }
        return true;
    }

    /**
     * URL格式
     */
    public function ruleUrl(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, $field . 'URL格式不正确');
            return false;
        }
        return true;
    }

    /**
     * 字母和数字
     */
    public function ruleAlphaNum(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!preg_match('/^[a-zA-Z0-9]+$/', (string) $value)) {
            $this->addError($field, $field . '只能包含字母和数字');
            return false;
        }
        return true;
    }

    /**
     * 数组类型
     */
    public function ruleArray(string $field, $value, ?string $param): bool
    {
        if ($value === null) {
            return true;
        }
        if (!is_array($value)) {
            $this->addError($field, $field . '必须是数组');
            return false;
        }
        return true;
    }

    /**
     * 布尔值
     */
    public function ruleBoolean(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $valid = [true, false, 0, 1, '0', '1', 'true', 'false'];
        if (!in_array($value, $valid, true)) {
            $this->addError($field, $field . '必须是布尔值');
            return false;
        }
        return true;
    }

    /**
     * 最小数值
     */
    public function ruleMin(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!is_numeric($value) || $value < (float) $param) {
            $this->addError($field, $field . '不能小于' . $param);
            return false;
        }
        return true;
    }

    /**
     * 最大数值
     */
    public function ruleMax(string $field, $value, ?string $param): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!is_numeric($value) || $value > (float) $param) {
            $this->addError($field, $field . '不能大于' . $param);
            return false;
        }
        return true;
    }

    /**
     * 检查验证是否通过
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * 检查验证是否失败
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * 获取所有错误
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 获取第一条错误消息
     */
    public function getFirstError(): string
    {
        foreach ($this->errors as $messages) {
            if (!empty($messages)) {
                return $messages[0];
            }
        }
        return '';
    }

    /**
     * 为字段添加错误消息
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
}
