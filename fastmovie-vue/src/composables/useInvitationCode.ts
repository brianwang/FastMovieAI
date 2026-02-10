import { ElMessageBox } from "element-plus"
import type { ElMessageBoxOptions } from "element-plus"
import { h } from "vue"
import XLInvitationCode from "@/components/xl-invitation-code/index.vue"

export const useInvitationCode = () => {
    const open = () => {
        const options: ElMessageBoxOptions = {
            showClose: true,
            showCancelButton: false,
            showConfirmButton: false,
            customStyle: {
                '--el-messagebox-width': 'min(586px,100%)',
                '--el-messagebox-border-radius': '20px',
                '--el-messagebox-padding-primary': '30px',
                '--el-color-primary':'var(--el-color-success)',
            },
            autofocus:false,
            closeOnPressEscape: true,
            closeOnClickModal: true,
            title: '邀请好友',
            message: () => h(XLInvitationCode),
        }
        ElMessageBox(options)
            .then(() => {
                console.log('打开邀请码成功')
            })
            .catch(() => {
                console.log('打开邀请码失败')
            })
    }
    
    const close = () => {
        ElMessageBox.close()
    }
    
    return {
        open,
        close,
    }
}