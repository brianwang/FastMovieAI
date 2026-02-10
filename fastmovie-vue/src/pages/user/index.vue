<template>
    <div class="container">
        <div class="flex flex-y-center flex-x-space-between">
            <div class="flex flex-y-center flex-x-center grid-gap-7">
                <el-avatar :size="75" :src="USERINFO?.headimg">
                    {{ truncate(USERINFO?.nickname, 1) }}
                </el-avatar>
                <div class="flex flex-column flex-x-start grid-gap-3">
                    <span class="h3 font-weight-bold text-ellipsis-1">{{ USERINFO?.nickname }}</span>
                    <div>ID：{{ USERINFO?.id }}</div>
                </div>
            </div>
            <div class="flex flex-y-center grid-gap-10">
                <div class="btn flex-shrink-0" @click="xlUserinfoRef.open">编辑主页</div>
                <div class="btn flex-shrink-0" @click="openInvitationCode()">邀请好友
                </div>
            </div>
        </div>
        <div class="flex flex-x-center my-7">
            <el-segmented v-model="activeName" :options="options" />
        </div>
        <div class="py-10" v-if="activeName === 'works'">
            <xl-tabs v-model="tabsActiveName" class="text-info" color="var(--el-color-white)">
                <xl-tabs-item value="works">创作作品</xl-tabs-item>
                <xl-tabs-item value="publish">发布作品</xl-tabs-item>
            </xl-tabs>
        </div>
        <Works v-if="activeName === 'works' && tabsActiveName === 'works'" />
        <Share v-if="activeName === 'works' && tabsActiveName === 'publish'" />
        <Actors v-if="activeName === 'actor'" />
        <Voice v-if="activeName === 'voice'" />
        <xl-userinfo ref="xlUserinfoRef" />
    </div>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import Works from './modules/works.vue';
import Actors from './modules/actors.vue';
import Voice from './modules/voice.vue';
import Share from './modules/share.vue';
import { useInvitationCode } from '@/composables/useInvitationCode';
import { truncate } from '@/common/functions';
import { useRefs, useUserStore } from '@/stores';

// ========== 状态 ==========
const activeName = ref('works')
const tabsActiveName = ref('works')
const xlUserinfoRef = ref<any>(null);
const useInvitationCodeBox = useInvitationCode();
// ========== 配置 ==========
const options = [{
    label: '我的作品',
    value: 'works',
}, {
    label: '我的演员',
    value: 'actor'
}, {
    label: '我的音色',
    value: 'voice'
}];

// ========== Store ==========
const userStore = useUserStore();
const { USERINFO } = useRefs(userStore);

// ========== 事件处理 ==========
const openInvitationCode = () => {
    useInvitationCodeBox.open();
}
</script>
<style scoped lang="scss">
.container {
    width: 100%;
    height: calc(100dvh - var(--xl-header-height));
    padding: 20px 150px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.btn {
    border-radius: 50px 50px 50px 50px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding:8px 30px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    &:hover {
        background-color: var(--el-color-success);
        color: var(--el-color-black);
    }
}

.el-segmented {
    --el-segmented-item-selected-color: var(--el-color-black);
    --el-segmented-item-selected-bg-color: var(--el-color-white);
    --el-border-radius-base: 8px;
}

:deep(.el-segmented__item) {
    padding: 4px 25px;
    font-size: 18px;
}

:deep(.el-segmented__item.is-selected) {
    font-weight: bold;
}

.text-info {
    height: 40px;
}
</style>