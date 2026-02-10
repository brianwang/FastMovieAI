<template>
    <div class="invitation-content">
        <!-- 邀请码列表 -->
        <div class="invitation-codes-section" v-if="invitationCodes.length > 0">
            <div class="h8">您还剩 {{ invitationCodes.filter((item: any) => item.status === 'unused').length }} 个邀请名额，拥有下方邀请码的任何人都可以加入</div>
            <div class="h8 mt-3">好友成功注册可得 {{ WEBCONFIG?.register?.invite_reward_points || 0 }} 积分</div>
            <div class="codes-grid">
                <div v-for="(item, index) in invitationCodes" :key="index" class="code-item">
                    <div class="flex  flex-1 grid-gap-3">
                        <div class="code-text">{{ item.code }}</div>
                        <div class="code-status">
                            <span v-if="item.status === 'used'" class="status-tag status-used">已使用</span>
                            <span v-else class="status-tag status-pending">待使用</span>
                            <!-- <div v-else-if="item.status === 'reward'" class="status-reward">
                                <el-icon class="star-icon">
                                    <StarFilled />
                                </el-icon>
                                <span>+{{ item.points || 100 }}</span>
                            </div> -->
                        </div>
                    </div>
                    <span @click="copyCodeItem(item.code)" v-if="item.status === 'unused'">复制</span>
                </div>
            </div>
        </div>
        <div v-else class="invitation-codes-section">
            <div class="h8">您的邀请名额已用完</div>
        </div>

        <!-- 邀请记录 -->
        <div class="invitation-records-section">
            <div class="records-title">邀请好友记录</div>
            <div class="records-list" v-if="invitationRecords.length > 0">
                <div v-for="(record, index) in invitationRecords" :key="index" class="record-item">
                    <div class="record-name">{{ record.useUser.nickname }}</div>
                    <div class="record-reward">
                        <span>+{{ record.points || WEBCONFIG?.register?.invite_reward_points }}</span>
                    </div>
                    <div class="record-date">{{ record.create_time }}</div>
                </div>
            </div>
            <div v-else class="h9 text-center records-list-empty">
                暂未邀请好友，复制邀请码邀请好友得积分吧~
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { $http } from '@/common/http';
import { ResponseCode } from '@/common/const';
import { ElMessage } from 'element-plus';
import { useRefs, useWebConfigStore } from '@/stores';

// ========== Store ==========
const webConfigStore = useWebConfigStore();
const { WEBCONFIG } = useRefs(webConfigStore);

// ========== 状态 ==========
const list = ref<any>([])
const invitationCodes = ref<any[]>([])
const invitationRecords = ref<any[]>([])

// ========== 业务逻辑 ==========
const getList = () => {
    $http.get('/app/user/api/User/getUnusedInvitationCode').then((res: any) => {
        if (res.code === ResponseCode.SUCCESS) {
            list.value = res.data
            // TODO: 将接口返回的数据转换为 invitationCodes 格式
            invitationCodes.value = res.data.map((item: any) => ({
                code: item.code,
                status: item.status,
                sort: item.status === 'unused' ? 0 : 1,
            })).sort((a: any, b: any) => a.sort - b.sort)
            invitationRecords.value = res.data.map((item: any) => { return item.status === 'used' ? item : null }).filter(Boolean)
            console.log(invitationRecords.value)
        }
    })
    // TODO: 获取邀请记录
    // getInvitationRecords()
}

// ========== 工具函数 ==========
const fallbackCopy = (text: string) => {
    const textarea = document.createElement('textarea')
    textarea.value = text
    textarea.style.position = 'fixed'
    textarea.style.left = '-9999px'
    textarea.style.top = '-9999px'
    document.body.appendChild(textarea)
    textarea.focus()
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
}

// ========== 事件处理 ==========
const copyCodeItem = async (code: string) => {
    if (!code) {
        ElMessage.warning('暂无可复制内容')
        return
    }
    // 获取当前域名并构建完整链接
    const origin = window.location.origin
    const url = `${origin}/fastmovie/#/?code=${code}`
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(url)
        } else {
            fallbackCopy(url)
        }
        ElMessage.success('复制成功')
    } catch (err) {
        ElMessage.error('复制失败')
    }
}

// ========== 副作用 ==========
onMounted(() => {
    getList()
})
</script>
<style scoped lang="scss">

.invitation-content {
    display: flex;
    flex-direction: column;
    gap: 24px;
    width: calc(586px - 60px);
}

.invitation-codes-section {
    text-align: center;

    .h8 {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.90);
        line-height: 1.5;
    }

    .codes-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-top: 20px;
        max-height: 240px;
        overflow-y: auto;
    }

    .code-item {
        display: flex;
        gap: 8px;
        background: #1E1E1E;
        border: 1px solid #272727;
        padding: 16px 20px;
        border-radius: 12px;
        cursor: pointer;
    }

    .code-text {
        font-size: 18px;
        color: #FFFFFF;
        letter-spacing: 1px;
    }

    .code-status {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .status-tag {
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 500;
        display: inline-block;
    }

    .status-used {
        background: rgba(140, 140, 140, 0.1);
        color: #8C8C8C;
        border: 1px solid #8C8C8C;
    }

    .status-pending {
        background: rgba(103, 194, 58, 0.15);
        color: var(--el-color-success);
        border: 1px solid var(--el-color-success);
    }

    .status-reward {
        display: flex;
        align-items: center;
        gap: 4px;
        color: #FFD700;
        font-weight: 600;
        font-size: 14px;

        .star-icon {
            font-size: 16px;
        }
    }

    .copy-btn {
        width: 100%;
        background-color: #fff;
        color: #000;
        border: 1px solid rgba(0, 0, 0, 0.1);
        font-weight: 600;
        font-size: 13px;
        padding: 6px 0;
        border-radius: 6px;
        transition: all 0.3s;

        &:hover {
            background-color: #f5f5f5;
            border-color: rgba(0, 0, 0, 0.15);
        }
    }
}

.invitation-records-section {
    .records-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 16px;
    }

    .records-list {
        display: flex;
        flex-direction: column;
        padding: 0px 16px;
        max-height: 200px;
        overflow-y: auto;
    }

    .records-list-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 120px;
    }

    .record-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        color: #FFFFFF;

        &:last-child {
            border-bottom: none;
        }
    }


    .record-name {
        font-size: 14px;
        width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        text-align: left;
    }

    .record-reward {
        font-size: 14px;
        color: #FFD700;
        margin: 0 12px;
        white-space: nowrap;
    }

    .record-date {
        font-size: 12px;
        color: #B5B5B5;
        white-space: nowrap;
    }
}
</style>