<?php

namespace plugin\shortplay\utils;

class PricingCalculator
{
    protected array $formRules;
    protected array $formData;

    protected int $baseCost;        // 👈 系统级基础积分
    protected int $extraPoint = 0;  // 规则附加积分
    protected float $multiplier = 1.0;

    protected array $chargeableComponents = [
        'select',
        'radio',
        'checkbox',
        'switch',
    ];

    public function __construct(array $formRules, array $formData, int $baseCost)
    {
        $this->formRules = $formRules;
        $this->formData  = $formData;
        $this->baseCost  = $baseCost;
    }

    /**
     * 主入口
     */
    public function calculate(): int
    {
        foreach ($this->formRules as $rule) {
            if (!in_array($rule['component'], $this->chargeableComponents, true)) {
                continue;
            }

            $value = $this->getSubmittedValue($rule);
            if ($value === null) {
                continue;
            }

            // Field 级计费（switch）
            $this->applyFieldPricing($rule, $value);

            // Option 级计费
            if (!empty($rule['options'])) {
                $this->applyOptionPricing($rule, $value);
            }
        }

        return (int) ceil($this->baseCost * $this->multiplier + $this->extraPoint);
    }

    protected function getSubmittedValue(array $rule)
    {
        $field = $rule['helper_field'];
        $data = $this->formData;
        if (isset($data[$field])) {
            return $data[$field];
        }
        return null;
    }

    /**
     * Field 级计费（switch）
     */
    protected function applyFieldPricing(array $rule, $value): void
    {
        if ($rule['component'] === 'switch' && $value !== true) {
            return;
        }

        if (isset($rule['base_point']) && $rule['base_point'] > 0) {
            $this->extraPoint += (int)$rule['base_point'];
        }

        if (isset($rule['multiplier']) && $rule['multiplier'] > 0) {
            $this->multiplier *= (float)$rule['multiplier'];
        }
    }

    /**
     * Option 级计费
     */
    protected function applyOptionPricing(array $rule, $value): void
    {
        foreach ($rule['options'] as $option) {
            if (!$this->optionMatched($rule['component'], $option, $value)) {
                continue;
            }

            if (isset($option['base_point'])) {
                if (is_numeric($value)) {
                    $this->extraPoint += (int)$option['base_point'] * (int)$value;
                } else {
                    $this->extraPoint += (int)$option['base_point'];
                }
            }

            if (isset($option['multiplier'])) {
                $this->multiplier *= (float)$option['multiplier'];
            }
        }
    }

    protected function optionMatched(string $component, array $option, $value): bool
    {
        if ($component === 'checkbox') {
            return is_array($value) && in_array($option['value'], $value, true);
        }

        return (string)$option['value'] === (string)$value;
    }
}
