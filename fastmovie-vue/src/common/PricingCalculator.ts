type FormRuleOption = {
    value: string | number | boolean
    base_point?: number
    multiplier?: number
}

type FormRule = {
    component: 'select' | 'radio' | 'checkbox' | 'switch' | string
    helper_field: string
    base_point?: number
    multiplier?: number
    options?: FormRuleOption[]
}

type FormData = Record<string, any>

export class PricingCalculator {
    protected formRules: FormRule[]
    protected formData: FormData

    protected baseCost: number          // 系统级基础积分
    protected extraPoint = 0             // 规则附加积分
    protected multiplier = 1.0

    protected chargeableComponents = [
        'select',
        'radio',
        'checkbox',
        'switch',
    ]

    constructor(formRules: FormRule[], formData: FormData, baseCost: number) {
        this.formRules = formRules
        this.formData = formData
        this.baseCost = baseCost
    }

    /**
     * 主入口
     */
    calculate(): number {
        for (const rule of this.formRules) {
            if (!this.chargeableComponents.includes(rule.component)) {
                continue
            }

            const value = this.getSubmittedValue(rule)
            if (value === null || value === undefined) {
                continue
            }

            // Field 级计费（switch）
            this.applyFieldPricing(rule, value)

            // Option 级计费
            if (rule.options && rule.options.length > 0) {
                this.applyOptionPricing(rule, value)
            }
        }
        return Math.ceil(this.baseCost * this.multiplier + this.extraPoint)
    }

    protected getSubmittedValue(rule: FormRule): any {
        const field = rule.helper_field
        if (field in this.formData) {
            return this.formData[field]
        }
        return null
    }

    /**
     * Field 级计费（switch）
     */
    protected applyFieldPricing(rule: FormRule, value: any): void {
        if (rule.component === 'switch' && value !== true) {
            return
        }

        if (rule.base_point !== undefined && Number(rule.base_point)) {
            this.extraPoint += Number(rule.base_point)
        }

        if (rule.multiplier !== undefined && Number(rule.multiplier)) {
            this.multiplier *= Number(rule.multiplier)
        }
    }

    /**
     * Option 级计费
     */
    protected applyOptionPricing(rule: FormRule, value: any): void {
        for (const option of rule.options!) {
            if (!this.optionMatched(rule.component, option, value)) {
                continue
            }

            if (option.base_point !== undefined) {
                if (typeof value === 'number') {
                    this.extraPoint += Number(option.base_point) * value
                } else {
                    this.extraPoint += Number(option.base_point)
                }
            }
            if (option.multiplier !== undefined) {
                this.multiplier = this.multiplier * Number(option.multiplier)
                console.log(this.multiplier, option.multiplier);
            }
        }
    }

    protected optionMatched(
        component: string,
        option: FormRuleOption,
        value: any
    ): boolean {
        if (component === 'checkbox') {
            return Array.isArray(value) && value.includes(option.value)
        }

        return String(option.value) === String(value)
    }
}
