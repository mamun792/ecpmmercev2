<template>
    <div class="space-y-6">
        <!-- Inventory Settings -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-200">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                    <Settings class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Inventory Settings</h3>
                    <p class="text-sm text-gray-600">Configure stock tracking and availability</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            v-model="inventorySettings.track_quantity"
                            type="checkbox"
                            class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                        />
                        <div>
                            <span class="text-sm font-medium text-gray-900">Track Quantity</span>
                            <p class="text-xs text-gray-600">Monitor stock levels for this product</p>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            v-model="inventorySettings.sell_without_stock"
                            type="checkbox"
                            class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                        />
                        <div>
                            <span class="text-sm font-medium text-gray-900">Sell Without Stock</span>
                            <p class="text-xs text-gray-600">Allow sales when out of stock</p>
                        </div>
                    </label>
                </div>

                <div v-if="inventorySettings.track_quantity">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Minimum Quantity Alert
                    </label>
                    <input
                        v-model="inventorySettings.min_quantity"
                        type="number"
                        min="0"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="0"
                    />
                    <p class="text-xs text-gray-500 mt-1">
                        Get alerts when stock falls below this level
                    </p>
                </div>
            </div>
        </div>

        <!-- Stock Locations -->
        <div v-if="inventorySettings.track_quantity" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                            <Package class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Stock Locations</h3>
                            <p class="text-sm text-gray-600">Set initial inventory across locations</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="addStockLocation"
                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2"
                    >
                        <PlusIcon class="w-4 h-4" />
                        Add Location
                    </button>
                </div>
            </div>

            <div class="p-6">
                <div v-if="stockData.length === 0" class="text-center py-8 text-gray-500">
                    <BoxIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                    <p class="font-medium">No stock locations added yet</p>
                    <p class="text-sm">Click "Add Location" to set initial inventory</p>
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="(stockItem, index) in stockData"
                        :key="index"
                        class="p-4 bg-gray-50 rounded-xl border border-gray-200"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <select
                                    v-model="stockItem.location"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="">Select Location</option>
                                    <option
                                        v-for="location in locations"
                                        :key="location.value"
                                        :value="location.value"
                                    >
                                        {{ location.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                <input
                                    v-model="stockItem.quantity"
                                    type="number"
                                    min="0"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="0"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <input
                                    v-model="stockItem.notes"
                                    type="text"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Optional notes"
                                />
                            </div>

                            <div class="flex items-end">
                                <button
                                    type="button"
                                    @click="removeStockLocation(index)"
                                    class="w-full px-3 py-2 text-red-600 border border-red-300 rounded-lg hover:bg-red-50 transition-colors flex items-center justify-center gap-2"
                                >
                                    <XIcon class="w-4 h-4" />
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Stock Summary -->
                <div v-if="stockData.length > 0" class="mt-6 p-4 bg-indigo-50 rounded-xl border border-indigo-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Sparkles class="w-5 h-5 text-indigo-600" />
                            <span class="font-medium text-indigo-900">Total Stock</span>
                        </div>
                        <span class="text-2xl font-bold text-indigo-600">{{ totalStockQuantity }} units</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Settings, Package, PlusIcon, BoxIcon, XIcon, Sparkles } from 'lucide-vue-next'

const props = defineProps({
    inventorySettings: {
        type: Object,
        default: () => ({
            track_quantity: true,
            sell_without_stock: false,
            min_quantity: 0
        })
    },
    stockData: {
        type: Array,
        default: () => []
    },
    locations: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:inventorySettings', 'update:stockData'])

const addStockLocation = () => {
    const newStockData = [...props.stockData, {
        location: '',
        quantity: '',
        notes: ''
    }]
    emit('update:stockData', newStockData)
}

const removeStockLocation = (index) => {
    const newStockData = props.stockData.filter((_, i) => i !== index)
    emit('update:stockData', newStockData)
}

const totalStockQuantity = computed(() => {
    return props.stockData.reduce((total, item) => {
        const quantity = parseInt(item.quantity) || 0
        return total + quantity
    }, 0)
})
</script>
