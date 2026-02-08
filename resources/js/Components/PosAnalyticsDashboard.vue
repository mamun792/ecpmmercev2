<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

// Props
const props = defineProps({
    show: {
        type: Boolean,
        default: false
    }
});

// Emits
const emit = defineEmits(['close', 'refresh-needed']);

// Reactive data
const analytics = ref(null);
const inventoryAlerts = ref([]);
const customerInsights = ref([]);
const loading = ref(false);
const error = ref(null);
const lastUpdated = ref(null);

// Auto-refresh timer
let refreshInterval = null;

// Computed properties
const todayStats = computed(() => analytics.value?.today || {});
const comparisonStats = computed(() => analytics.value?.comparison || {});
const popularProducts = computed(() => analytics.value?.popular_products || []);
const paymentMethods = computed(() => analytics.value?.payment_methods || []);
const performanceMetrics = computed(() => analytics.value?.performance_metrics || {});

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount || 0);
};

// Format percentage
const formatPercentage = (value) => {
    return `${(value || 0).toFixed(1)}%`;
};

// Format number safely
const formatNumber = (value, decimals = 1) => {
    const num = Number(value) || 0;
    return num.toFixed(decimals);
};

// Get growth indicator
const getGrowthIndicator = (growth) => {
    if (growth > 0) return { icon: '📈', color: 'text-green-600', class: 'bg-green-50' };
    if (growth < 0) return { icon: '📉', color: 'text-red-600', class: 'bg-red-50' };
    return { icon: '➖', color: 'text-gray-600', class: 'bg-gray-50' };
};

// Fetch analytics data
const fetchAnalytics = async () => {
    try {
        loading.value = true;
        error.value = null;

        const [analyticsRes, alertsRes, insightsRes] = await Promise.all([
            axios.get('/admin/api/pos-analytics/dashboard'),
            axios.get('/admin/api/pos-analytics/inventory-alerts'),
            axios.get('/admin/api/pos-analytics/customer-insights')
        ]);

        analytics.value = analyticsRes.data.data;
        inventoryAlerts.value = alertsRes.data.data;
        customerInsights.value = insightsRes.data.data;
        lastUpdated.value = new Date();

    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to fetch analytics';
        console.error('Analytics fetch error:', {
            error: err,
            status: err.response?.status,
            data: err.response?.data,
            url: err.config?.url
        });
    } finally {
        loading.value = false;
    }
};

// Refresh cache
const refreshCache = async () => {
    try {
        await axios.post('/admin/api/pos-analytics/refresh-cache');
        await fetchAnalytics();
        emit('refresh-needed');
    } catch (err) {
        error.value = 'Failed to refresh cache';
        console.error('Cache refresh error:', err);
    }
};

// Start auto-refresh
const startAutoRefresh = () => {
    refreshInterval = setInterval(fetchAnalytics, 30000); // 30 seconds
};

// Stop auto-refresh
const stopAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
};

// Component lifecycle
onMounted(() => {
    if (props.show) {
        fetchAnalytics();
        startAutoRefresh();
    }
});

onUnmounted(() => {
    stopAutoRefresh();
});

// Watch for show prop changes
watch(() => props.show, (newValue) => {
    if (newValue) {
        fetchAnalytics();
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
});

// Close modal
const closeModal = () => {
    stopAutoRefresh();
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <Transition name="analytics-modal">
            <div
                v-if="show"
                class="analytics-overlay"
                @click.self="closeModal"
            >
                <div class="analytics-modal">
                    <!-- Header -->
                    <div class="analytics-header">
                        <div class="analytics-header-left">
                            <div class="analytics-header-icon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            </div>
                            <div>
                                <h2 class="analytics-title">POS Analytics</h2>
                                <p class="analytics-subtitle">
                                    Real-time insights
                                    <span v-if="lastUpdated" class="analytics-updated">
                                        · Updated {{ lastUpdated.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="analytics-header-actions">
                            <button @click="refreshCache" :disabled="loading" class="analytics-btn-refresh" title="Refresh">
                                <svg :class="{ 'analytics-spin': loading }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                            </button>
                            <button @click="closeModal" class="analytics-btn-close">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="analytics-body">
                        <!-- Loading -->
                        <div v-if="loading && !analytics" class="analytics-state-container">
                            <div class="analytics-loader">
                                <div class="analytics-loader-ring"></div>
                            </div>
                            <p class="analytics-state-text">Loading analytics...</p>
                        </div>

                        <!-- Error -->
                        <div v-else-if="error" class="analytics-state-container">
                            <div class="analytics-error-icon">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </div>
                            <p class="analytics-error-title">Failed to load data</p>
                            <p class="analytics-error-msg">{{ error }}</p>
                            <button @click="fetchAnalytics" class="analytics-retry-btn">
                                Try Again
                            </button>
                        </div>

                        <!-- Data -->
                        <div v-else-if="analytics" class="analytics-content">

                            <!-- KPI Cards -->
                            <div class="analytics-kpi-grid">
                                <div class="analytics-kpi analytics-kpi-blue">
                                    <div class="analytics-kpi-top">
                                        <span class="analytics-kpi-label">Orders Today</span>
                                        <span class="analytics-kpi-badge" :class="comparisonStats.order_growth >= 0 ? 'analytics-badge-green' : 'analytics-badge-red'">
                                            {{ comparisonStats.order_growth >= 0 ? '↑' : '↓' }} {{ formatNumber(Math.abs(comparisonStats.order_growth || 0), 1) }}%
                                        </span>
                                    </div>
                                    <div class="analytics-kpi-value">{{ todayStats.total_orders || 0 }}</div>
                                    <div class="analytics-kpi-sub">vs {{ comparisonStats.yesterday_orders || 0 }} yesterday</div>
                                    <div class="analytics-kpi-bar">
                                        <div class="analytics-kpi-bar-fill analytics-bar-blue" :style="{ width: Math.min(100, ((todayStats.total_orders || 0) / Math.max(comparisonStats.yesterday_orders || 1, 1)) * 100) + '%' }"></div>
                                    </div>
                                </div>

                                <div class="analytics-kpi analytics-kpi-green">
                                    <div class="analytics-kpi-top">
                                        <span class="analytics-kpi-label">Revenue</span>
                                        <span class="analytics-kpi-badge" :class="comparisonStats.revenue_growth >= 0 ? 'analytics-badge-green' : 'analytics-badge-red'">
                                            {{ comparisonStats.revenue_growth >= 0 ? '↑' : '↓' }} {{ formatNumber(Math.abs(comparisonStats.revenue_growth || 0), 1) }}%
                                        </span>
                                    </div>
                                    <div class="analytics-kpi-value">{{ formatCurrency(todayStats.revenue) }}</div>
                                    <div class="analytics-kpi-sub">vs {{ formatCurrency(comparisonStats.yesterday_revenue) }} yesterday</div>
                                    <div class="analytics-kpi-bar">
                                        <div class="analytics-kpi-bar-fill analytics-bar-green" :style="{ width: Math.min(100, ((todayStats.revenue || 0) / Math.max(comparisonStats.yesterday_revenue || 1, 1)) * 100) + '%' }"></div>
                                    </div>
                                </div>

                                <div class="analytics-kpi analytics-kpi-violet">
                                    <div class="analytics-kpi-top">
                                        <span class="analytics-kpi-label">Avg Order Value</span>
                                        <span class="analytics-kpi-badge analytics-badge-violet">AVG</span>
                                    </div>
                                    <div class="analytics-kpi-value">{{ formatCurrency(todayStats.avg_order_value) }}</div>
                                    <div class="analytics-kpi-sub">{{ formatPercentage(todayStats.payment_rate) }} paid</div>
                                </div>

                                <div class="analytics-kpi analytics-kpi-amber">
                                    <div class="analytics-kpi-top">
                                        <span class="analytics-kpi-label">Customers</span>
                                        <span class="analytics-kpi-badge analytics-badge-amber">TODAY</span>
                                    </div>
                                    <div class="analytics-kpi-value">{{ todayStats.unique_customers || 0 }}</div>
                                    <div class="analytics-kpi-sub">{{ formatNumber(performanceMetrics.items_per_order, 1) }} items/order avg</div>
                                </div>
                            </div>

                            <!-- Two Column Section -->
                            <div class="analytics-two-col">
                                <!-- Top Products -->
                                <div class="analytics-card">
                                    <div class="analytics-card-header">
                                        <h3 class="analytics-card-title">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2"><path d="M8.5 14.5A2.5 2.5 0 0011 12c0-1.38-.5-2-1-3-1.07-2.14 0-5.5 4.5-7.5C13 4 13.5 7.5 15 9.5c.5.67 1 2.5 1 4a4 4 0 01-8 0z"/></svg>
                                            Top Products
                                        </h3>
                                        <span class="analytics-card-count">Today</span>
                                    </div>
                                    <div v-if="!popularProducts.length" class="analytics-empty">
                                        <p>No sales recorded today</p>
                                    </div>
                                    <div v-else class="analytics-list">
                                        <div v-for="(item, index) in popularProducts.slice(0, 5)" :key="item.product?.id" class="analytics-list-item">
                                            <div class="analytics-rank" :class="'analytics-rank-' + (index < 3 ? index + 1 : 'default')">
                                                {{ index + 1 }}
                                            </div>
                                            <div class="analytics-list-info">
                                                <span class="analytics-list-name">{{ item.product?.name || 'Unknown' }}</span>
                                                <span class="analytics-list-meta">{{ item.total_sold }} sold · {{ item.order_count }} orders</span>
                                            </div>
                                            <div class="analytics-list-value">{{ formatCurrency(item.revenue) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Methods -->
                                <div class="analytics-card">
                                    <div class="analytics-card-header">
                                        <h3 class="analytics-card-title">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                            Payment Methods
                                        </h3>
                                    </div>
                                    <div v-if="!paymentMethods.length" class="analytics-empty">
                                        <p>No payment data yet</p>
                                    </div>
                                    <div v-else class="analytics-list">
                                        <div v-for="method in paymentMethods" :key="method.method" class="analytics-list-item">
                                            <div class="analytics-payment-icon" :class="'analytics-pay-' + (method.method || 'other')">
                                                {{ method.method === 'Cash on Delivery' || method.method === 'cod' ? '💵' : method.method === 'card' || method.method === 'online' ? '💳' : method.method === 'bkash' ? '📱' : '💰' }}
                                            </div>
                                            <div class="analytics-list-info">
                                                <span class="analytics-list-name" style="text-transform: capitalize;">{{ method.method || 'Unknown' }}</span>
                                                <span class="analytics-list-meta">{{ method.count }} orders · {{ formatCurrency(method.avg_value) }} avg</span>
                                            </div>
                                            <div class="analytics-list-value">{{ formatCurrency(method.revenue) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Alerts & Customers Row -->
                            <div class="analytics-two-col">
                                <!-- Stock Alerts -->
                                <div class="analytics-card">
                                    <div class="analytics-card-header">
                                        <h3 class="analytics-card-title">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                            Stock Alerts
                                        </h3>
                                        <span v-if="inventoryAlerts.length" class="analytics-alert-count">{{ inventoryAlerts.length }}</span>
                                    </div>
                                    <div v-if="!inventoryAlerts.length" class="analytics-empty analytics-empty-success">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        <p>All products well stocked!</p>
                                    </div>
                                    <div v-else class="analytics-list">
                                        <div v-for="alert in inventoryAlerts.slice(0, 5)" :key="alert.id"
                                            class="analytics-alert-item"
                                            :class="alert.urgency === 'critical' ? 'analytics-alert-critical' : 'analytics-alert-warning'"
                                        >
                                            <div class="analytics-alert-indicator" :class="alert.urgency === 'critical' ? 'analytics-indicator-red' : 'analytics-indicator-yellow'"></div>
                                            <div class="analytics-list-info">
                                                <span class="analytics-list-name">{{ alert.name }}</span>
                                                <span class="analytics-list-meta">{{ alert.category }} · {{ alert.current_stock }} left / min {{ alert.threshold }}</span>
                                            </div>
                                            <span class="analytics-stock-badge" :class="alert.urgency === 'critical' ? 'analytics-stock-critical' : 'analytics-stock-warning'">
                                                {{ alert.urgency === 'critical' ? 'Critical' : 'Low' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Top Customers -->
                                <div class="analytics-card">
                                    <div class="analytics-card-header">
                                        <h3 class="analytics-card-title">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                                            Top Customers
                                        </h3>
                                        <span class="analytics-card-count">Today</span>
                                    </div>
                                    <div v-if="!customerInsights.length" class="analytics-empty">
                                        <p>No customer data yet</p>
                                    </div>
                                    <div v-else class="analytics-list">
                                        <div v-for="customer in customerInsights.slice(0, 5)" :key="customer.user?.id" class="analytics-list-item">
                                            <div class="analytics-avatar">
                                                {{ customer.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                                            </div>
                                            <div class="analytics-list-info">
                                                <span class="analytics-list-name">{{ customer.user?.name || 'Walk-in Customer' }}</span>
                                                <span class="analytics-list-meta">
                                                    {{ customer.order_count }} order{{ customer.order_count !== 1 ? 's' : '' }}
                                                    · <span :class="customer.customer_type === 'returning' ? 'analytics-tag-returning' : 'analytics-tag-new'">{{ customer.customer_type }}</span>
                                                </span>
                                            </div>
                                            <div class="analytics-list-value">{{ formatCurrency(customer.total_spent) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Performance Strip -->
                            <div class="analytics-perf-strip">
                                <div class="analytics-perf-item">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    <div>
                                        <span class="analytics-perf-value">{{ formatNumber(performanceMetrics.avg_time_between_orders, 0) }}m</span>
                                        <span class="analytics-perf-label">Avg Gap</span>
                                    </div>
                                </div>
                                <div class="analytics-perf-divider"></div>
                                <div class="analytics-perf-item">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                    <div>
                                        <span class="analytics-perf-value">{{ performanceMetrics.peak_hour ? `${performanceMetrics.peak_hour.hour}:00` : '—' }}</span>
                                        <span class="analytics-perf-label">Peak Hour</span>
                                    </div>
                                </div>
                                <div class="analytics-perf-divider"></div>
                                <div class="analytics-perf-item">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                    <div>
                                        <span class="analytics-perf-value">{{ formatPercentage(performanceMetrics.conversion_rate) }}</span>
                                        <span class="analytics-perf-label">Conversion</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════
   POS Analytics Dashboard - Fully Scoped Styles
   No global leaks, self-contained modal design
   ═══════════════════════════════════════════════════ */

/* Overlay */
.analytics-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
}

/* Modal Container */
.analytics-modal {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
    width: 100%;
    max-width: 1100px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

/* Header */
.analytics-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: #fff;
    flex-shrink: 0;
}

.analytics-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.analytics-header-icon {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.analytics-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
    color: #fff;
}

.analytics-subtitle {
    font-size: 12px;
    color: #94a3b8;
    margin: 2px 0 0;
}

.analytics-updated {
    color: #64748b;
}

.analytics-header-actions {
    display: flex;
    gap: 8px;
}

.analytics-btn-refresh,
.analytics-btn-close {
    all: unset;
    cursor: pointer;
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: rgba(255,255,255,0.1);
    color: #fff;
    transition: background 0.2s;
}
.analytics-btn-refresh:hover,
.analytics-btn-close:hover {
    background: rgba(255,255,255,0.2);
}
.analytics-btn-refresh:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Body */
.analytics-body {
    padding: 20px 24px;
    overflow-y: auto;
    flex: 1;
}

/* States */
.analytics-state-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    gap: 12px;
}
.analytics-state-text {
    color: #64748b;
    font-size: 14px;
}

/* Loader */
.analytics-loader {
    width: 40px;
    height: 40px;
    position: relative;
}
.analytics-loader-ring {
    width: 100%;
    height: 100%;
    border: 3px solid #e2e8f0;
    border-top-color: #6366f1;
    border-radius: 50%;
    animation: analytics-ring-spin 0.8s linear infinite;
}
@keyframes analytics-ring-spin {
    to { transform: rotate(360deg); }
}

.analytics-spin {
    animation: analytics-ring-spin 0.8s linear infinite;
}

/* Error */
.analytics-error-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}
.analytics-error-msg {
    font-size: 13px;
    color: #ef4444;
    margin: 0;
    text-align: center;
    max-width: 400px;
}
.analytics-retry-btn {
    all: unset;
    cursor: pointer;
    padding: 8px 20px;
    background: #1e293b;
    color: #fff;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    margin-top: 4px;
    transition: background 0.2s;
}
.analytics-retry-btn:hover {
    background: #334155;
}

/* Content */
.analytics-content {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ── KPI Cards ── */
.analytics-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

.analytics-kpi {
    padding: 16px;
    border-radius: 12px;
    border: 1px solid;
    position: relative;
    overflow: hidden;
}
.analytics-kpi::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 60px;
    height: 60px;
    border-radius: 0 0 0 60px;
    opacity: 0.06;
}

.analytics-kpi-blue { background: #eff6ff; border-color: #bfdbfe; }
.analytics-kpi-blue::after { background: #3b82f6; }
.analytics-kpi-green { background: #f0fdf4; border-color: #bbf7d0; }
.analytics-kpi-green::after { background: #22c55e; }
.analytics-kpi-violet { background: #f5f3ff; border-color: #ddd6fe; }
.analytics-kpi-violet::after { background: #8b5cf6; }
.analytics-kpi-amber { background: #fffbeb; border-color: #fde68a; }
.analytics-kpi-amber::after { background: #f59e0b; }

.analytics-kpi-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.analytics-kpi-label {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.analytics-kpi-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
}
.analytics-badge-green { background: #dcfce7; color: #15803d; }
.analytics-badge-red { background: #fee2e2; color: #dc2626; }
.analytics-badge-violet { background: #ede9fe; color: #7c3aed; }
.analytics-badge-amber { background: #fef3c7; color: #b45309; }

.analytics-kpi-value {
    font-size: 26px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
}

.analytics-kpi-sub {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 4px;
}

.analytics-kpi-bar {
    height: 4px;
    background: rgba(0,0,0,0.06);
    border-radius: 4px;
    margin-top: 10px;
    overflow: hidden;
}
.analytics-kpi-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.6s ease;
}
.analytics-bar-blue { background: #3b82f6; }
.analytics-bar-green { background: #22c55e; }

/* ── Cards ── */
.analytics-two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.analytics-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.analytics-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
}

.analytics-card-title {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.analytics-card-count {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 500;
}

.analytics-alert-count {
    font-size: 11px;
    font-weight: 700;
    background: #fee2e2;
    color: #dc2626;
    padding: 2px 8px;
    border-radius: 20px;
}

/* ── List Items ── */
.analytics-list {
    padding: 4px 0;
}

.analytics-list-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    transition: background 0.15s;
}
.analytics-list-item:hover {
    background: #f8fafc;
}

.analytics-list-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.analytics-list-name {
    font-size: 13px;
    font-weight: 500;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.analytics-list-meta {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 1px;
}

.analytics-list-value {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
    white-space: nowrap;
}

/* Rank Badges */
.analytics-rank {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}
.analytics-rank-1 { background: #fef3c7; color: #b45309; }
.analytics-rank-2 { background: #e2e8f0; color: #475569; }
.analytics-rank-3 { background: #fed7aa; color: #c2410c; }
.analytics-rank-default { background: #f1f5f9; color: #94a3b8; }

/* Payment Icon */
.analytics-payment-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
    background: #f1f5f9;
}

/* Avatar */
.analytics-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

/* Alert Items */
.analytics-alert-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
}
.analytics-alert-critical { background: #fef2f2; }
.analytics-alert-warning { background: #fffbeb; }

.analytics-alert-indicator {
    width: 4px;
    height: 32px;
    border-radius: 4px;
    flex-shrink: 0;
}
.analytics-indicator-red { background: #ef4444; }
.analytics-indicator-yellow { background: #f59e0b; }

.analytics-stock-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    flex-shrink: 0;
}
.analytics-stock-critical { background: #fee2e2; color: #dc2626; }
.analytics-stock-warning { background: #fef3c7; color: #b45309; }

.analytics-tag-returning { color: #16a34a; font-weight: 500; }
.analytics-tag-new { color: #2563eb; font-weight: 500; }

/* Empty State */
.analytics-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
    color: #94a3b8;
    gap: 8px;
    font-size: 13px;
}
.analytics-empty-success {
    color: #22c55e;
}

/* ── Performance Strip ── */
.analytics-perf-strip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 24px;
}

.analytics-perf-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 24px;
}
.analytics-perf-item > div {
    display: flex;
    flex-direction: column;
}
.analytics-perf-value {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}
.analytics-perf-label {
    font-size: 11px;
    color: #94a3b8;
}
.analytics-perf-divider {
    width: 1px;
    height: 32px;
    background: #e2e8f0;
}

/* ── Transitions ── */
.analytics-modal-enter-active {
    transition: all 0.25s ease-out;
}
.analytics-modal-leave-active {
    transition: all 0.2s ease-in;
}
.analytics-modal-enter-from {
    opacity: 0;
}
.analytics-modal-enter-from .analytics-modal {
    transform: scale(0.95) translateY(10px);
    opacity: 0;
}
.analytics-modal-leave-to {
    opacity: 0;
}
.analytics-modal-leave-to .analytics-modal {
    transform: scale(0.95) translateY(10px);
    opacity: 0;
}

/* ── Responsive ── */
@media (max-width: 900px) {
    .analytics-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .analytics-two-col {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 600px) {
    .analytics-kpi-grid {
        grid-template-columns: 1fr;
    }
    .analytics-body {
        padding: 14px 16px;
    }
    .analytics-header {
        padding: 14px 16px;
    }
    .analytics-perf-strip {
        flex-direction: column;
        gap: 12px;
    }
    .analytics-perf-divider {
        width: 100%;
        height: 1px;
    }
    .analytics-perf-item {
        padding: 0;
    }
    .analytics-kpi-value {
        font-size: 22px;
    }
}
</style>
