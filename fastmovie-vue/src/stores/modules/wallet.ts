import { ResponseCode } from '@/common/const';
import { $http } from '@/common/http';
import { useStorage } from '@/composables/useStorage';
export default () => {
    const storage = useStorage();
    const WALLET = ref<WalletInterface>({
        uid: '',
        channels_uid: 0,
        balance: 0,
        balance_sum: 0,
        balance_used: 0,
        points: 0,
        points_sum: 0,
        points_used: 0,
        tmp_points: 0,
        tmp_points_sum: 0,
        tmp_points_used: 0,
        available_points: 0,
    });
    const initWallet = () => {
        const data = storage.get('WALLET');
        if (data) {
            WALLET.value = data as WalletInterface;
        }
    }
    const setWallet = (data: WalletInterface) => {
        WALLET.value = data;
        storage.set('WALLET', data, 3600);
    }
    const getWallet = () => {
        $http.get('/app/user/api/User/wallet').then((res: any) => {
            if (res.code === ResponseCode.SUCCESS) {
                setWallet(res.data as WalletInterface);
            }
        });
    }
    return {
        WALLET,
        initWallet,
        setWallet,
        getWallet
    };
}