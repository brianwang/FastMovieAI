<template>
    <div class="container">
        <div class="stars small" ref="smallStars"></div>
        <div class="stars big" ref="bigStars"></div>

        <IconCloseSvg class="close-btn" @click="router.back()" />
        <div class="h1 font-weight-bold">充值积分</div>

        <div class="mt-7">
            <span>选择适合你的积分套餐或</span>
            <span class="text-success cursor-pointer" @click="router.push('/vip')">
                订阅会员
            </span>
        </div>

        <div class="content">
            <el-scrollbar height="314px" max-height="314px">
                <div class="list">
                    <div v-for="item in pointsList" :key="item.id" class="item"
                        :class="{ selected: pointsId === item.id }" @click="pointsId = item.id">
                        <div class="flex flex-column flex-1">
                            <div class="h3 font-weight-bold flex flex-y-center grid-gap-1">
                                <IconPointsSvg />
                                <span>{{ item.points + item.give }}</span>

                                <div v-if="item.discount > 0" class="discount">
                                    限时特惠
                                </div>
                            </div>

                            <div class="text-secondary text-ellipsis-2">{{ item.desc }}</div>
                        </div>

                        <div class="flex flex-y-baseline grid-gap-4">
                            <Price :price="item.price" />
                            <span class="price-original">¥{{ item.original_price }}</span>
                        </div>
                    </div>
                </div>
            </el-scrollbar>
        </div>

        <!-- <div class="btn-wrapper"> -->
        <el-button class="btn" color="#ffffff" round @click="submit">
            立即充值
        </el-button>
        <!-- </div> -->

        <xl-pay ref="payDialogRef" type="points" :id="pointsId" :price="currentPrice" />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'

import Price from './modules/price.vue'
import IconPointsSvg from '@/svg/icon/icon-points.vue'
import { $http } from '@/common/http'
import { ResponseCode } from '@/common/const'
import IconCloseSvg from '@/svg/icon/icon-close.vue'

const router = useRouter()

const pointsList = ref<any[]>([])
const pointsId = ref<number | null>(null)
const payDialogRef = ref<{ open: () => void } | null>(null)

const currentPrice = computed(() => {
    return pointsList.value.find(i => i.id === pointsId.value)?.price
})

const getPointsList = async () => {
    const res: any = await $http.get('/app/marketing/api/Points/index')
    if (res.code === ResponseCode.SUCCESS) {
        pointsList.value = res.data || []
    }
}

const submit = () => {
    if (!pointsId.value) {
        ElMessage.error('请选择积分套餐')
        return
    }
    payDialogRef.value?.open()
}

const bigStars = ref<HTMLDivElement | null>(null)
const smallStars = ref<HTMLDivElement | null>(null)

const generateStars = (count: number, width: number, height: number) => {
    return Array.from({ length: count }, () =>
        `${Math.random() * width}px ${Math.random() * height}px white`
    ).join(',')
}

const initStars = () => {
    const w = window.innerWidth
    const h = window.innerHeight

    if (bigStars.value) {
        bigStars.value.style.boxShadow = generateStars(50, w, h)
    }
    if (smallStars.value) {
        smallStars.value.style.boxShadow = generateStars(100, w, h)
    }
}

onMounted(() => {
    initStars()
    getPointsList()
})
</script>

<style scoped lang="scss">
.container {
    position: relative;
    width: 100vw;
    height: 100dvh;
    overflow: hidden;
    color: #fff;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    .close-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        cursor: pointer;
        z-index: 1;
        color: #fff;
    }
}

.content {
    margin-top: 60px;
    overflow: hidden;

    .list {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-gap: 20px;
        width: 1030px;
        z-index: 1;

        .item {
            cursor: pointer;
            height: 147px;
            padding: 20px;

            background: var(--el-bg-color-overlay);
            border: 1px solid #272727;
            border-radius: 20px;

            display: flex;
            flex-direction: column;
            justify-content: space-between;

            &.selected {
                border-color: #ffffff;
            }

            .discount {
                padding: 4px 10px;
                font-size: 14px;
                color: #febc2f;
                background: rgba(254, 188, 47, 0.1);
                border-radius: 30px;
            }

        }
    }
}

.price-original {
    color: #8c8c8c;
    text-decoration: line-through;
}

.btn-wrapper {
    // position: relative;
    // margin-top: 60px;
    // width: 290px;
    // border-radius: 30px;
    // padding: 2px;
    // background: #ffffff;
    // overflow: hidden;

    // &::before {
    //     content: '';
    //     position: absolute;
    //     inset: 0;
    //     border-radius: 30px;
    //     padding: 2px;
    //     background: conic-gradient(
    //         from 0deg,
    //         transparent 0deg,
    //         transparent 320deg,
    //         #ff6b6b 330deg,
    //         #4ecdc4 340deg,
    //         #45b7d1 350deg,
    //         #f9ca24 360deg,
    //         #f0932b 370deg,
    //         #eb4d4b 380deg,
    //         transparent 390deg,
    //         transparent 360deg
    //     );
    //     -webkit-mask: 
    //         linear-gradient(#fff 0 0) content-box, 
    //         linear-gradient(#fff 0 0);
    //     -webkit-mask-composite: xor;
    //     mask: 
    //         linear-gradient(#fff 0 0) content-box, 
    //         linear-gradient(#fff 0 0);
    //     mask-composite: exclude;
    //     animation: marquee-border 2s linear infinite;
    // }

    // &::after {
    //     content: '';
    //     position: absolute;
    //     inset: 2px;
    //     background: #ffffff;
    //     border-radius: 28px;
    //     z-index: 1;
    // }

    .btn {
        margin-top: 30px;
        width: 290px;
        padding: 18px 60px;
    }
}

@keyframes marquee-border {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

.stars {
    position: absolute;
    inset: 0;
    background: white;
    border-radius: 50%;
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
</style>