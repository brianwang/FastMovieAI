<template>
    <el-dialog v-model="visible" width="590px" align-center append-to-body @close="list = [];query.page = 1">
        <template #header>
            <div class="flex flex-y-center  grid-gap-7 flex-1">
                <el-avatar :size="54" :src="USERINFO?.headimg">
                    {{ truncate(USERINFO?.nickname, 1) }}
                </el-avatar>
                <div class="flex flex-column flex-x-start grid-gap-1">
                    <span class="h8 font-weight-bold">{{ USERINFO?.nickname }}</span>
                    <div class="h9 text-secondary">剩余积分 ： <span class="text-success"> {{ WALLET?.points
                            }}</span> </div>
                </div>
            </div>
        </template>
        <div class="w-p-100 my-5 px-3">
            <div class="flex flex-y-center">
                <el-segmented v-model="query.type" :options="options" @change="onChangeType" />
            </div>
            <el-scrollbar height="400px" max-height="60vh" v-loading="loading" @end-reached="handleScroll">
                <div class="flex flex-y-center grid-gap-4 py-5 line" v-for="item in list" :key="item.id"
                    v-if="list.length > 0">
                    <div class="flex flex-column flex-1 grid-gap-1">
                        <span class="h8">{{ item.remarks }}</span>
                        <span class="text-secondary h9">{{ item.create_time }}</span>
                    </div>
                    <span class="h7 text-success" v-if="item.action === 'increase'">+{{ item.num }}</span>
                    <span class="h7" v-if="item.action === 'decrease'">-{{ item.num }}</span>
                </div>
                <div v-else>
                    <el-empty description="暂无数据" />
                </div>
            </el-scrollbar>
        </div>
        <template #footer>
            <div class="flex flex-x-center grid-gap-6">
                <div class="flex-1 text-success flex flex-y-center grid-gap-2 pointer" @click="router.push('/article/points')">
                    <el-icon :size="18">
                        <Warning />
                    </el-icon>
                    <span>积分规则</span>
                </div>
                <el-button class="btn" color="var(--el-color-white)" size="large" @click="router.push('/points')">
                    充值积分
                </el-button>
                <el-button class="btn" color="var(--el-color-success)" size="large" @click="router.push('/vip')">
                    订阅服务
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>
<script setup lang="ts">
import { truncate } from '@/common/functions';
import { ref } from 'vue';
import { useRefs, useUserStore, useWalletStore } from '@/stores';
import { useRouter } from 'vue-router';
import { $http } from '@/common/http';
import { ResponseCode } from '@/common/const';
const router = useRouter();
const userStore = useUserStore();
const { USERINFO } = useRefs(userStore);
const walletStore = useWalletStore();
const { WALLET } = useRefs(walletStore);
const visible = ref(false);
const loading = ref(false);
const options = [
    { label: '全部', value: 'all' },
    { label: '消耗', value: 'consume' },
    { label: '充值', value: 'recharge' },
    { label: '获得', value: 'gain' },
];
const list = ref<any[]>([]);
const total = ref(0);
const query = ref({
    page: 1,
    type: 'all',
});
const getPointsList = async () => {
    loading.value = true;
    const res: any = await $http.get('/app/user/api/Bill/getPointsBill', { params: query.value })
    if (res.code === ResponseCode.SUCCESS) {
        list.value = [...list.value, ...(res.data.data || [])]
        total.value = res.data.total || 0
    }
    loading.value = false;
}
const onChangeType = () => {
    list.value = [];
    query.value.page = 1;
    getPointsList();
}
const handleScroll = () => {
    console.log('handleScroll');
    if (list.value.length >= total.value) return;
    query.value.page++;
    getPointsList();
}
defineExpose({
    open: () => {
        visible.value = true
        getPointsList()
    }
})
</script>
<style scoped lang="scss">
.btn {
    padding: 8px 30px;
}

.el-segmented {
    --el-segmented-item-selected-color: var(--el-color-black);
    --el-segmented-item-selected-bg-color: var(--el-color-white);
    --el-border-radius-base: 8px;
}

:deep(.el-segmented__item) {
    padding: 4px 48px;
    font-size: 18px;
}

:deep(.el-segmented__item.is-selected) {
    font-weight: bold;
}

.line {
    border-bottom: 1px solid var(--el-border-color-lighter);
}
</style>