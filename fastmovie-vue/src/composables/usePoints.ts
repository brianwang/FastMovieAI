import { PricingCalculator } from "@/common/PricingCalculator";

export const usePoints = (models: any[], num: Ref<number> = ref(1), form: Ref<any> = ref()) => {
    const points = computed(() => {
        let totalPoints = 0;
        for (const model of models) {
            if (model && model.value && model.value.id) {
                if (form.value) {
                    const calculator = new PricingCalculator(model.value.form, form.value, model.value.unit_point);
                    const point = calculator.calculate();
                    totalPoints += point || 0;
                } else {
                    totalPoints += model.value.unit_point || 0;
                }
            }
        }
        totalPoints *= num.value;
        if (totalPoints === 0) {
            return '免费';
        }
        return totalPoints;
    })
    return points
}