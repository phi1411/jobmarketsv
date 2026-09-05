<?php

namespace JobMarket\Http\Validation;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->validate();
    }

    public static function make(array $data, array $rules): static
    {
        return new static($data, $rules);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode("|", (string)$ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $rule = trim($rule);
                if ($rule === "") continue;

                $param = null;
                if (strpos($rule, ":") !== false) {
                    [$ruleName, $param] = explode(":", $rule, 2);
                } else {
                    $ruleName = $rule;
                }

                $this->applyRule($field, $value, $ruleName, $param);
            }
        }
    }

    private function applyRule(string $field, mixed $value, string $ruleName, ?string $param): void
    {
        switch ($ruleName) {
            case "required":
                if ($value === null || (is_string($value) && trim($value) === "") || (is_array($value) && empty($value))) {
                    $this->addError($field, "Trường {$field} là bắt buộc.");
                }
                break;

            case "email":
                if ($value !== null && $value !== "" && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Trường {$field} phải là định dạng email hợp lệ.");
                }
                break;

            case "min":
                $min = (int)$param;
                if ($value !== null && $value !== "") {
                    if (is_numeric($value) && (float)$value < $min) {
                        $this->addError($field, "Trường {$field} phải có giá trị tối thiểu là {$min}.");
                    } elseif (is_string($value) && mb_strlen($value) < $min) {
                        $this->addError($field, "Trường {$field} phải có tối thiểu {$min} ký tự.");
                    }
                }
                break;

            case "max":
                $max = (int)$param;
                if ($value !== null && $value !== "") {
                    if (is_numeric($value) && (float)$value > $max) {
                        $this->addError($field, "Trường {$field} không được vượt quá {$max}.");
                    } elseif (is_string($value) && mb_strlen($value) > $max) {
                        $this->addError($field, "Trường {$field} không được vượt quá {$max} ký tự.");
                    }
                }
                break;

            case "in":
                if ($value !== null && $value !== "") {
                    $allowed = explode(",", (string)$param);
                    if (!in_array((string)$value, $allowed, true)) {
                        $this->addError($field, "Trường {$field} phải là một trong các giá trị: " . implode(", ", $allowed) . ".");
                    }
                }
                break;

            case "numeric":
                if ($value !== null && $value !== "" && !is_numeric($value)) {
                    $this->addError($field, "Trường {$field} phải là số.");
                }
                break;

            case "string":
                if ($value !== null && !is_string($value)) {
                    $this->addError($field, "Trường {$field} phải là chuỗi ký tự.");
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
}
