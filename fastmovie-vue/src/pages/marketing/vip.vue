<template>
    <div class="container">
        <div class="stars small" ref="smallStars"></div>
        <div class="stars big" ref="bigStars"></div>

        <IconCloseSvg class="close-btn" @click="router.back()" />
        <div class="h1 font-weight-bold">订阅会员畅想更多权益</div>
        <div class="mt-7">
            <span>选择适合你的会员套餐或</span>
            <span class="text-success cursor-pointer" @click="router.push('/points')">
                充值积分
            </span>
        </div>

        <div class="custom-style">
            <el-segmented v-model="billingCycle" :options="options" />
        </div>

        <div class="content">
            <div v-for="vip in vipListWithPrice" :key="vip.key" class="item-box"
                :class="{ 'item-selected': selectedVip === vip.key }" @click="selectedVip = vip.key">
                <div class="h4 font-weight-bold">
                    {{ VIP_META[vip.key].title }}
                </div>
                <div class="h10 text-secondary">
                    {{ VIP_META[vip.key].desc }}
                </div>

                <div class="flex-1">
                    <div class="flex flex-y-baseline grid-gap-4 mt-6">
                        <Price :price="vip.currentPrice.price" :size="36" :currency-size="22" :decimal-size="36" />
                        <span class="price-original">
                            {{ vip.currentPrice.original_price }}
                        </span>
                    </div>

                    <div class="h6 mt-4">
                        赠送 {{ vip.currentPrice.points }} 积分
                    </div>
                    <div class="h10 text-secondary">{{ vip.name }}</div>
                </div>
                <el-scrollbar >
                    <div class="mt-6 text-dark " v-for="(val, idx) in vip.description.split('|')" :key="idx">
                        {{ val }}
                    </div>
                </el-scrollbar>

                <el-button class="btn" color="#ffffff" round @click.stop="submit(vip)">
                    订阅计划
                </el-button>
            </div>
        </div>
        <xl-pay ref="payDialogRef" type="vip" :id="planId" :price="currentPrice" />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import Price from './modules/price.vue'
import { $http } from '@/common/http'
import { ResponseCode } from '@/common/const'
import IconCloseSvg from '@/svg/icon/icon-close.vue'

const payDialogRef = ref<any>(null)
const planId = ref<any>(null)
const currentPrice = ref<any>(null)
const router = useRouter()
const billingCycle = ref<'month' | 'year'>('month')
const selectedVip = ref<string | null>(null)
const vipList = ref<any[]>([])

const VIP_META: Record<string, any> = {
    basic: {
        title: '基础会员',
        desc: '适合个人创作者轻量编辑'
    },
    pro: {
        title: '高级会员',
        desc: '适合企业及专业团队使用'
    }
}

const options = [
    { label: '按月购买', value: 'month' },
    { label: '按年购买', value: 'year' }
]

const vipListWithPrice = computed(() => {
    return vipList.value.map(vip => {
        const current = vip.price.find(
            (p: any) => p.billing_cycle === billingCycle.value
        )
        return {
            ...vip,
            currentPrice: current
        }
    })
})

const getVipList = async () => {
    const res: any = await $http.get('/app/marketing/api/vip/index')
    if (res.code === ResponseCode.SUCCESS) {
        const order = ['basic', 'pro']
        vipList.value = res.data.sort(
            (a: any, b: any) => order.indexOf(a.key) - order.indexOf(b.key)
        )
    }
}

const submit = (vip: any) => {
    planId.value = vip.currentPrice.id
    currentPrice.value = vip.currentPrice.price
    nextTick(() => {
        payDialogRef.value?.open();
    })
}

const bigStars = ref<HTMLDivElement | null>(null)
const smallStars = ref<HTMLDivElement | null>(null)

const generateStars = (count: number) => {
    const w = window.innerWidth
    const h = window.innerHeight
    return Array.from({ length: count }, () =>
        `${Math.random() * w}px ${Math.random() * h}px white`
    ).join(',')
}

const renderStars = () => {
    if (bigStars.value)
        bigStars.value.style.boxShadow = generateStars(50)
    if (smallStars.value)
        smallStars.value.style.boxShadow = generateStars(100)
}

onMounted(() => {
    renderStars()
    window.addEventListener('resize', renderStars)
    getVipList()
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', renderStars)
})
</script>

<style scoped lang="scss">
.container {
    color: #fff;
    position: relative;
    height: 100dvh;
    width: 100vw;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.close-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    color: #fff;
    cursor: pointer;
    z-index: 1;
}

.content {
    display: flex;
    gap: 20px;
    margin-top: 20px;
    z-index: 1;
}

.item-box {
    width: 330px;
    height: 500px;
    border-radius: 20px;
    background-color: var(--el-bg-color-overlay);
    border: 1px solid #272727;
    padding: 20px;
    display: flex;
    flex-direction: column;
    cursor: pointer;
    transition: transform .25s ease, border-color .25s ease;

    &:hover {
        transform: translateY(-6px);
        border-color: rgba(255, 255, 255, 0.4);
    }
}

.item-selected {
    border-color: #ffffff !important;
}

.price-original {
    text-decoration: line-through;
    color: #8c8c8c;
}

.btn {
    margin-top: 30px;
    width: 290px;
    padding: 18px 0;
}

.stars {
    position: absolute;
    top: 0;
    left: 0;
    background: white;
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
}

.stars.small {
    width: 1px;
    height: 1px;
    filter: blur(0.5px);
}

.stars.big {
    width: 2px;
    height: 2px;
    filter: blur(0.8px);
}

.cursor-pointer {
    cursor: pointer;
}

.custom-style .el-segmented {
    --el-segmented-item-selected-color: #000;
    --el-segmented-item-selected-bg-color: #fff;
    --el-border-radius-base: 30px;
    font-size: 18px;
    margin-top: 50px;
}
</style>

<style>
.custom-style .el-segmented__item {
    padding: 2px 20px !important;
}

.custom-style .el-segmented__item.is-selected {
    font-weight: 600 !important;
}
</style>