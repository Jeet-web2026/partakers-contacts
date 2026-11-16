<div class="p-6">
    <div class="flex items-center gap-3 mb-2">
        <i class="bi bi-lightning-charge text-amber-400 text-2xl"></i>
        <div>
            <h2 class="text-lg font-semibold text-white">Recharge Wallet</h2>
            <p class="text-xs text-gray-400">Add credits to your wallet</p>
        </div>
    </div>

    <div class="h-px bg-slate-700 my-5"></div>

    <div class="bg-slate-800/60 border border-slate-700 rounded-xl p-5 max-w-md">
        <div class="flex flex-col items-center text-center gap-4 py-8">
            <div class="w-16 h-16 rounded-full bg-amber-500/20 flex items-center justify-center">
                <i class="bi bi-lightning-charge-fill text-amber-400 text-3xl"></i>
            </div>
            <div>
                <h3 class="text-base font-medium text-white">Recharge Wallet</h3>
                <p class="text-sm text-gray-400 mt-1">Add funds to continue using premium features</p>
            </div>
            <button type="button" wire:click="proceedToPurchase" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-black font-semibold text-sm rounded-lg transition-colors flex items-center gap-2">
                <i class="bi bi-plus-circle-fill"></i>
                Recharge Now
            </button>
        </div>
    </div>
</div>
