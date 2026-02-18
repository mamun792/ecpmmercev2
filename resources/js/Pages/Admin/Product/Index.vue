
<template>
  <Head title="Products Management" />
  <AdminLayout>
    <div class="max-w-[1600px] mx-auto px-3 sm:px-5 lg:px-8 py-5 sm:py-8">
      <div class="flex flex-col lg:flex-row gap-5 lg:gap-8">

        <!-- ══════════ LEFT SIDEBAR: Filters ══════════ -->
        <aside class="w-full lg:w-[270px] xl:w-[290px] shrink-0">
          <FilterSidebar
            :filters="filters"
            :categories="categories"
            :brands="brands"
          />
        </aside>

        <!-- ══════════ RIGHT CONTENT ══════════ -->
        <main class="flex-1 min-w-0 space-y-5">

          <!-- ─── Stats Cards ─── -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
            <div
              v-for="(val, label) in stats" :key="label"
              class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all duration-300 p-4 sm:p-5 group cursor-default"
            >
              <!-- Decorative blob -->
              <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-orange-50 dark:bg-orange-900/10 group-hover:scale-150 transition-transform duration-500"></div>
              <p class="relative text-[10px] sm:text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2 truncate">
                {{ label.replace(/_/g, ' ') }}
              </p>
              <div class="relative flex items-end justify-between">
                <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-gray-50 tabular-nums leading-none">{{ val }}</p>
                <span class="text-2xl opacity-40 group-hover:opacity-70 transition-opacity">
                  {{ label.includes('total') ? '📦' : label.includes('published') ? '🟢' : label.includes('low') ? '⚠️' : '🚨' }}
                </span>
              </div>
            </div>
          </div>

          <!-- ─── Main Table Card ─── -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">

            <!-- Card Header -->
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <!-- Title -->
                <div class="flex items-center gap-3 min-w-0">
                  <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-md flex-shrink-0">
                    <Package class="w-5 h-5 text-white" />
                  </div>
                  <div class="min-w-0">
                    <h1 class="text-lg sm:text-xl font-black text-gray-900 dark:text-gray-100 leading-tight truncate">Product Management</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-0.5">
                      Showing <span class="font-bold text-gray-700 dark:text-gray-300">{{ from }}–{{ to }}</span> of <span class="font-bold text-orange-600">{{ total }}</span> products
                    </p>
                  </div>
                </div>
                <!-- CTA Button -->
                <Link
                  :href="route('admin.products.create')"
                  class="group inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-orange-500/25 hover:shadow-orange-500/40 transition-all duration-200 active:scale-95 flex-shrink-0"
                >
                  <Plus class="w-4 h-4 group-hover:rotate-90 transition-transform duration-300" />
                  <span>Add New Product</span>
                </Link>
              </div>

              <!-- Status Badges Legend -->
              <div class="flex flex-wrap items-center gap-3 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700/50">
                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 px-2.5 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                  Published = Live
                </span>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-gray-500 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-full">
                  <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                  Draft = Hidden
                </span>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2.5 py-1 rounded-full">
                  <ChevronDown class="w-3 h-3" />
                  Expand for variations
                </span>
              </div>
            </div>


            <!-- Bulk Actions Bar (shows when items selected) -->
            <div v-if="selectedProductIds.length > 0"
                 class="mx-4 sm:mx-6 mt-3 mb-1 flex flex-wrap items-center gap-2 px-3 sm:px-4 py-2.5 bg-orange-50 dark:bg-orange-900/10 border border-orange-200 dark:border-orange-800/40 rounded-xl">
              <span class="text-xs font-black text-orange-700 dark:text-orange-400 uppercase tracking-tight whitespace-nowrap">
                {{ selectedProductIds.length }} Selected
              </span>
              <div class="h-4 w-px bg-orange-200 dark:bg-orange-800"></div>

                  <!-- Bulk Actions Dropdown -->
                  <div class="relative" ref="bulkActionsRef">
                    <button @click="toggleBulkActions" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-gray-900 border border-orange-200 dark:border-orange-800 rounded-lg text-[11px] font-bold text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-all shadow-sm">
                      <span>⚙️ Bulk Actions</span>
                      <ChevronDown class="w-3 h-3 transition-transform duration-200" :class="{ 'rotate-180': showBulkActions }" />
                    </button>

                    <!-- Dropdown Menu -->
                    <div v-if="showBulkActions" class="absolute left-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden">
                      <!-- Status Update -->
                      <div class="p-3 border-b border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Update Status</p>
                        <div class="flex gap-2">
                          <button @click="bulkUpdateStatus('Published')" class="flex-1 px-3 py-2 bg-green-50 hover:bg-green-100 text-green-700 rounded-xl text-xs font-bold transition-all active:scale-95">
                            ✅ Publish
                          </button>
                          <button @click="bulkUpdateStatus('Unpublished')" class="flex-1 px-3 py-2 bg-gray-50 hover:bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold transition-all active:scale-95">
                            ⏸️ Unpublish
                          </button>
                        </div>
                      </div>

                      <!-- Price Update -->
                      <div class="p-3 border-b border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Adjust Prices</p>
                        <div class="flex gap-2 mb-2">
                          <input v-model="bulkPriceValue" type="number" min="0" max="100" placeholder="%" class="w-20 px-2 py-1.5 text-xs border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 rounded-xl focus:ring-2 focus:ring-orange-400/30 outline-none" />
                          <button @click="bulkUpdatePrice('increase')" class="flex-1 px-2 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-xs font-bold active:scale-95">
                            📈 Increase
                          </button>
                          <button @click="bulkUpdatePrice('decrease')" class="flex-1 px-2 py-1.5 bg-orange-50 hover:bg-orange-100 text-orange-700 rounded-xl text-xs font-bold active:scale-95">
                            📉 Decrease
                          </button>
                        </div>
                      </div>

                      <!-- Category Assignment -->
                      <div class="p-3 border-b border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Assign Category</p>
                        <select v-model="bulkCategoryId" class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 rounded-xl focus:ring-2 focus:ring-orange-400/30 outline-none">
                          <option value="">Select Category...</option>
                          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <button @click="bulkAssignCategory" :disabled="!bulkCategoryId" class="w-full mt-2 px-3 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-xl text-xs font-bold disabled:opacity-40 transition-all active:scale-95">
                          📂 Assign Now
                        </button>
                      </div>

                      <!-- Delete -->
                      <div class="p-3 bg-red-50/50 dark:bg-red-900/10">
                        <input v-model="confirmationInput" type="text" placeholder="Type 'confirm' to delete" class="w-full px-3 py-1.5 text-xs bg-white dark:bg-gray-900 border border-red-200 dark:border-red-800 rounded-xl mb-2 focus:ring-2 focus:ring-red-400/30 outline-none" />
                        <button @click="deleteSelectedProducts" :disabled="confirmationInput !== 'confirm' || isDeleting" class="w-full px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-xl text-xs font-black disabled:opacity-40 transition-all active:scale-95">
                          {{ isDeleting ? '⏳ Deleting...' : '🗑️ Delete Selected' }}
                        </button>
                      </div>
                    </div>
                  </div>
            </div>

            <!-- ─── Sort & Per Page Toolbar ─── -->
            <div class="px-4 sm:px-6 py-3 bg-gray-50/60 dark:bg-gray-900/40 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                <!-- Desktop Sort -->
                <div class="hidden sm:flex items-center gap-3">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest whitespace-nowrap">Sort By:</span>
                    <div class="flex items-center bg-white dark:bg-gray-800 rounded-xl p-1 border border-gray-200 dark:border-gray-700 shadow-sm gap-1">
                        <button
                            v-for="opt in sortOptions" :key="opt.value"
                            @click="handleSort(opt.value)"
                            :class="[sortBy === opt.value
                              ? 'bg-orange-500 text-white shadow-md shadow-orange-500/20'
                              : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700']"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200 flex items-center gap-1.5 whitespace-nowrap"
                        >
                            {{ opt.label }}
                            <template v-if="sortBy === opt.value">
                                <ArrowUp v-if="sortOrder === 'asc'" class="w-3 h-3" />
                                <ArrowDown v-else class="w-3 h-3" />
                            </template>
                        </button>
                    </div>
                </div>

                <!-- Mobile Sort Button -->
                <div class="sm:hidden">
                  <button @click="openSortModal" class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-bold shadow-sm active:scale-95">
                    <ArrowUpDown class="w-4 h-4" />
                    <span>Sort: {{ sortOptions.find(o => o.value === sortBy)?.label }}</span>
                  </button>
                </div>

                <!-- Mobile Sort Bottom-sheet -->
                <div v-if="showSortModal" class="fixed inset-0 z-50 flex items-end sm:hidden">
                  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeSortModal"></div>
                  <div id="sort-modal" tabindex="-1" role="dialog" aria-modal="true" class="relative w-full bg-white dark:bg-gray-800 rounded-t-3xl p-6 max-h-[80vh] overflow-y-auto">
                    <div class="w-10 h-1 bg-gray-200 dark:bg-gray-600 rounded-full mx-auto mb-5"></div>
                    <div class="flex items-center justify-between mb-4">
                      <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">Sort Products</h3>
                      <button @click="closeSortModal" class="w-8 h-8 flex items-center justify-center bg-gray-100 dark:bg-gray-700 rounded-full text-gray-500 font-bold">✕</button>
                    </div>
                    <div class="space-y-2">
                      <button v-for="opt in sortOptions" :key="opt.value" @click="handleSortAndClose(opt.value)"
                              :class="[sortBy === opt.value ? 'bg-orange-500 text-white shadow-md' : 'bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300']"
                              class="w-full text-left px-4 py-3 rounded-xl font-bold flex items-center justify-between transition-all active:scale-95">
                        <span>{{ opt.label }}</span>
                        <div v-if="sortBy === opt.value">
                          <ArrowUp v-if="sortOrder === 'asc'" class="w-4 h-4" />
                          <ArrowDown v-else class="w-4 h-4" />
                        </div>
                      </button>
                    </div>
                    <button @click="toggleSortOrder" class="w-full mt-3 px-4 py-3 rounded-xl bg-gray-100 dark:bg-gray-700 font-bold text-gray-700 dark:text-gray-200 active:scale-95 transition-all">
                      Order: <span class="font-black">{{ sortOrder === 'asc' ? '↑ Ascending' : '↓ Descending' }}</span>
                    </button>
                  </div>
                </div>

                <!-- Per Page Selector -->
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest whitespace-nowrap hidden sm:inline">Display:</span>
                    <select
                        v-model="perPage"
                        @change="handlePerPageChange"
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-400/40 focus:border-orange-400 outline-none transition-all shadow-sm cursor-pointer"
                    >
                        <option value="25">25 / page</option>
                        <option value="50">50 / page</option>
                        <option value="100">100 / page</option>
                    </select>
                </div>
            </div>

        <!-- ─── Responsive Data Table ─── -->
        <div class="overflow-x-auto custom-scrollbar">
          <div class="inline-block min-w-full align-middle">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700/60">
              <thead>
                <tr class="bg-gray-50 dark:bg-gray-900/60">
                  <!-- Select -->
                  <th class="px-3 sm:px-4 py-3 text-left">
                    <input type="checkbox"
                      class="w-4 h-4 rounded border-2 border-gray-300 text-orange-500 focus:ring-orange-400/40 cursor-pointer"
                      :checked="selectedProductIds.length === productData.length && productData.length > 0"
                      @change="toggleSelectAll"
                      title="Select all" />
                  </th>
                  <th class="px-2 sm:px-4 py-3 text-left text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Image
                  </th>
                  <th class="px-3 sm:px-4 py-3 text-left text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[180px] sm:min-w-[220px]">
                    Product
                  </th>
                  <th class="hidden md:table-cell px-4 py-3 text-left text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[140px]">
                    SKU / Code
                  </th>
                  <th class="px-3 sm:px-4 py-3 text-left text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Price
                  </th>
                  <th class="px-3 sm:px-4 py-3 text-left text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Stock
                  </th>
                  <th class="hidden xl:table-cell px-4 py-3 text-left text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Analytics
                  </th>
                  <th class="hidden lg:table-cell px-4 py-3 text-left text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-3 sm:px-4 py-3 text-center text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Actions
                  </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/40">
            <!-- Skeleton Loader -->
            <template v-if="loading">
              <tr v-for="i in 5" :key="`skeleton-${i}`" class="animate-pulse">
                <td class="px-4 py-4"><div class="h-4 w-4 bg-gray-100 dark:bg-gray-700 rounded"></div></td>
                <td class="px-4 py-4"><div class="w-12 h-12 sm:w-14 sm:h-14 bg-gray-100 dark:bg-gray-700 rounded-xl"></div></td>
                <td class="px-4 py-4 space-y-2">
                  <div class="h-4 bg-gray-100 dark:bg-gray-700 rounded w-3/4"></div>
                  <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded w-1/2"></div>
                </td>
                <td class="hidden md:table-cell px-4 py-4"><div class="h-4 bg-gray-100 dark:bg-gray-700 rounded w-24"></div></td>
                <td class="px-4 py-4"><div class="h-4 bg-gray-100 dark:bg-gray-700 rounded w-20"></div></td>
                <td class="px-4 py-4"><div class="h-4 bg-gray-100 dark:bg-gray-700 rounded w-16"></div></td>
                <td class="hidden xl:table-cell px-4 py-4"><div class="h-16 bg-gray-100 dark:bg-gray-700 rounded w-24"></div></td>
                <td class="hidden lg:table-cell px-4 py-4"><div class="h-6 bg-gray-100 dark:bg-gray-700 rounded-full w-16"></div></td>
                <td class="px-4 py-4"><div class="h-8 bg-gray-100 dark:bg-gray-700 rounded-lg w-24 mx-auto"></div></td>
              </tr>
            </template>

            <!-- Actual Data -->
            <template v-else>
              <template v-for="(item, index) in productData" :key="item.id || `product-${index}`">
                <!-- Product Row -->
                <tr class="group hover:bg-orange-50/20 dark:hover:bg-orange-900/5 transition-colors duration-150"
                    :class="{ 'bg-orange-50/10 dark:bg-orange-900/5': isSelected(item.id) }">

                  <!-- Checkbox -->
                  <td class="px-3 sm:px-4 py-3 sm:py-4">
                    <input type="checkbox"
                      class="w-4 h-4 rounded border-2 border-gray-300 dark:border-gray-600 text-orange-500 focus:ring-2 focus:ring-orange-400/40 cursor-pointer"
                      :checked="isSelected(item.id)" @change="toggleSelection(item.id)" />
                  </td>

                  <!-- Image -->
                  <td class="px-2 sm:px-4 py-3 sm:py-4">
                    <div class="relative w-12 h-12 sm:w-14 sm:h-14 flex-shrink-0 group/img">
                        <div class="product-image-wrapper w-full h-full rounded-xl overflow-hidden bg-gray-50 dark:bg-gray-900 ring-1 ring-gray-200 dark:ring-gray-700 shadow-sm">
                          <img class="product-image w-full h-full object-cover"
                            :src="getFeatureImageUrl(item)"
                            :alt="item.name"
                            loading="lazy" />
                        </div>
                        <!-- Gallery count badge -->
                        <div v-if="item.gallery_images && item.gallery_images.length > 0"
                             class="absolute -bottom-1 -right-1 bg-blue-500 text-white px-1.5 py-0.5 rounded-md text-[8px] font-black shadow-sm">
                          {{ item.gallery_images.length + 1 }}
                        </div>
                        <!-- Daily deal badge -->
                        <div v-if="item.is_daily_product"
                             class="absolute -top-1.5 -left-1.5 bg-amber-400 text-white text-[8px] font-black px-1.5 py-0.5 rounded-md shadow-sm whitespace-nowrap">
                          HOT
                        </div>
                    </div>
                  </td>

                  <!-- Product Details -->
                  <td class="px-3 sm:px-4 py-3 sm:py-4 max-w-[200px] sm:max-w-[260px]">
                    <div class="space-y-1.5">
                      <h3
                        class="font-bold text-sm text-gray-900 dark:text-gray-100 leading-snug line-clamp-2 hover:text-orange-600 dark:hover:text-orange-400 transition-colors cursor-pointer"
                        @click="toggleExpanded(item.id)"
                        :title="item.name"
                      >{{ item.name }}</h3>
                      <!-- Short desc -->
                      <p v-if="item.short_description" class="text-xs text-gray-400 dark:text-gray-500 line-clamp-1 hidden sm:block">
                        {{ getShortDescription(item, 70) }}
                      </p>
                      <!-- Variation preview -->
                      <div v-if="item.type === 'variable' && item.variations?.length" class="hidden sm:flex flex-wrap gap-1">
                        <template v-for="(v, idx) in getCompactVariations(item)" :key="`cv-${item.id}-${idx}`">
                          <span :class="[v.isMore ? 'text-blue-500' : (v.isDefault ? 'font-bold text-gray-700 dark:text-gray-300' : 'text-gray-400')]"
                                class="text-[10px]">
                            {{ v.label }}{{ idx < getCompactVariations(item).length - 1 ? ', ' : '' }}
                          </span>
                        </template>
                      </div>
                      <!-- Type badge -->
                      <div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-tight"
                          :class="item.type === 'simple'
                            ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400'
                            : 'bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400'">
                          {{ item.type }}
                        </span>
                      </div>
                    </div>
                  </td>

                  <!-- SKU / Identity (hidden on mobile) -->
                  <td class="hidden md:table-cell px-4 py-3 sm:py-4">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[9px] font-bold text-gray-300 dark:text-gray-600 uppercase w-10 shrink-0">SKU</span>
                            <code class="px-1.5 py-0.5 bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-400 rounded text-[10px] font-mono border border-gray-100 dark:border-gray-800 truncate max-w-[110px]">
                                {{ item.product_code || '—' }}
                            </code>
                        </div>
                        <div v-if="item.barcode" class="flex items-center gap-1.5">
                            <span class="text-[9px] font-bold text-gray-300 dark:text-gray-600 uppercase w-10 shrink-0">CODE</span>
                            <code class="px-1.5 py-0.5 bg-blue-50/50 dark:bg-blue-900/10 text-blue-600 dark:text-blue-400 rounded text-[10px] font-mono border border-blue-100 dark:border-blue-900/30 truncate max-w-[110px]">
                                {{ item.barcode }}
                            </code>
                        </div>
                    </div>
                  </td>

                  <!-- Price -->
                  <td class="px-3 sm:px-4 py-3 sm:py-4 whitespace-nowrap">
                    <div class="space-y-1">
                      <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-sm sm:text-base font-black text-gray-900 dark:text-gray-100 tabular-nums">৳{{ formatCurrency(getDisplayPrice(item)) }}</span>
                        <span v-if="item.type === 'variable'" class="text-[9px] text-gray-400 font-bold uppercase hidden sm:inline">VAR</span>
                        <span v-if="getPreviousPrice(item) !== null && getPreviousPrice(item) > getDisplayPrice(item)"
                              class="px-1.5 py-0.5 bg-red-500 text-white text-[8px] font-black rounded-full">
                          SALE
                        </span>
                      </div>
                      <div v-if="getPreviousPrice(item) !== null && getPreviousPrice(item) > getDisplayPrice(item)" class="flex items-center gap-1.5">
                        <span class="text-[10px] text-gray-400 line-through tabular-nums">৳{{ formatCurrency(getPreviousPrice(item)) }}</span>
                        <span class="text-[10px] font-bold text-green-600 bg-green-50 dark:bg-green-900/20 px-1.5 rounded">
                          -{{ Math.round(((getPreviousPrice(item) - getDisplayPrice(item)) / getPreviousPrice(item)) * 100) }}%
                        </span>
                      </div>
                    </div>
                  </td>

                  <!-- Stock -->
                  <td class="px-3 sm:px-4 py-3 sm:py-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-1.5">
                             <div class="w-2 h-2 rounded-full flex-shrink-0 shadow-sm"
                                  :class="item.stock > 10 ? 'bg-green-500' : (item.stock > 0 ? 'bg-amber-500' : 'bg-red-500')"></div>
                             <span class="text-xs sm:text-sm font-bold text-gray-700 dark:text-gray-300 tabular-nums whitespace-nowrap">
                               {{ item.stock }} <span class="hidden sm:inline text-[10px] font-medium text-gray-400">units</span>
                             </span>
                        </div>
                        <span class="inline-block text-[9px] sm:text-[10px] font-bold uppercase tracking-tight px-2 py-0.5 rounded-full"
                              :class="item.stock > 10
                                ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400'
                                : (item.stock > 0
                                  ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400'
                                  : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400')">
                            {{ item.stock > 10 ? 'Good' : (item.stock > 0 ? 'Low' : 'Out') }}
                        </span>
                        <!-- Variation stock breakdown (sm+) -->
                        <div v-if="item.type === 'variable' && item.variations?.length" class="hidden sm:block mt-1 space-y-0.5">
                          <p class="text-[9px] text-blue-500 font-semibold">{{ item.variations.length }} variations</p>
                          <template v-for="(variation, idx) in item.variations.slice(0, 2)" :key="variation.id">
                            <div class="flex items-center justify-between gap-2 text-[9px]">
                              <span class="text-gray-400 truncate max-w-[70px]" :title="getVariationLabel(variation)">
                                {{ getVariationLabel(variation) }}
                              </span>
                              <span class="font-bold text-gray-600 dark:text-gray-400 shrink-0">
                                {{ variation.inventory_stock?.available_quantity ?? 0 }}u
                              </span>
                            </div>
                          </template>
                          <p v-if="item.variations.length > 2" class="text-[9px] text-blue-500">+{{ item.variations.length - 2 }} more</p>
                        </div>
                    </div>
                  </td>

                  <!-- Analytics (XL only) -->
                  <td class="hidden xl:table-cell px-4 py-3 sm:py-4">
                    <div class="space-y-2">
                      <div class="flex items-center justify-between gap-3">
                        <span class="text-[9px] font-bold text-gray-400 uppercase whitespace-nowrap">7d Sales</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black tabular-nums"
                              :class="item.sales_count_7_days >= 10 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : (item.sales_count_7_days >= 5 ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500')">
                          {{ item.sales_count_7_days || 0 }}
                        </span>
                      </div>
                      <div class="flex items-center justify-between gap-3">
                        <span class="text-[9px] font-bold text-gray-400 uppercase whitespace-nowrap">30d</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black tabular-nums"
                              :class="item.sales_count_30_days >= 30 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500'">
                          {{ item.sales_count_30_days || 0 }}
                        </span>
                      </div>
                      <div class="flex items-center justify-between gap-3">
                        <span class="text-[9px] font-bold text-gray-400 uppercase">Revenue</span>
                        <span class="text-[10px] font-black text-green-600 dark:text-green-400 tabular-nums">৳{{ formatCurrency(item.total_revenue || 0) }}</span>
                      </div>
                      <div class="flex items-center justify-between gap-3">
                        <span class="text-[9px] font-bold text-gray-400 uppercase">Margin</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                              :class="item.profit_margin >= 30 ? 'bg-green-100 text-green-700' : (item.profit_margin >= 15 ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')">
                          {{ item.profit_margin || 0 }}%
                        </span>
                      </div>
                      <!-- Performance badge -->
                      <div class="pt-1 border-t border-gray-100 dark:border-gray-700">
                        <span class="inline-flex items-center gap-1 text-[9px] font-black uppercase"
                              :class="item.performance_score === 'fast' ? 'text-green-600' : (item.performance_score === 'moderate' ? 'text-blue-600' : 'text-gray-400')">
                          <span class="w-1.5 h-1.5 rounded-full"
                                :class="item.performance_score === 'fast' ? 'bg-green-500 animate-pulse' : (item.performance_score === 'moderate' ? 'bg-blue-500' : 'bg-gray-400')"></span>
                          {{ item.performance_score === 'fast' ? 'Fast Moving' : (item.performance_score === 'moderate' ? 'Moderate' : 'Slow') }}
                        </span>
                      </div>
                    </div>
                  </td>

                  <!-- Status (lg+, mobile handled in actions) -->
                  <td class="hidden lg:table-cell px-4 py-3 sm:py-4 whitespace-nowrap">
                    <button
                        @click="toggleStatus(item)"
                        class="group/toggle flex items-center gap-2"
                        :title="item.status === 'Published' ? 'Click to unpublish' : 'Click to publish'"
                    >
                      <div class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-300 ease-in-out focus:outline-none shadow-inner"
                           :class="item.status === 'Published' ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'">
                        <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-md ring-0 transition duration-300 ease-in-out"
                              :class="item.status === 'Published' ? 'translate-x-4' : 'translate-x-0'" />
                      </div>
                      <span class="text-xs font-bold"
                            :class="item.status === 'Published' ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                        {{ item.status === 'Published' ? 'Live' : 'Draft' }}
                      </span>
                    </button>
                  </td>

                  <!-- Actions -->
                  <td class="px-2 sm:px-4 py-3 sm:py-4">
                    <div class="flex items-center justify-center gap-0.5 sm:gap-1">
                      <!-- Edit -->
                      <Link :href="route('admin.products.edit', item.id)"
                           class="p-1.5 sm:p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all active:scale-90"
                           title="Edit product">
                        <SquarePen class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                      </Link>
                      <!-- Clone -->
                      <button @click="cloneProduct(item.id)"
                             class="p-1.5 sm:p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-all active:scale-90"
                             title="Duplicate product">
                        <LayoutTemplate class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                      </button>
                      <!-- Delete -->
                      <button @click="openDeleteModal(item.id)"
                             class="p-1.5 sm:p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all active:scale-90"
                             title="Delete product">
                        <Trash2Icon class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                      </button>
                      <!-- Expand -->
                      <button @click="toggleExpanded(item.id)"
                             class="p-1.5 sm:p-2 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all active:scale-90"
                             :title="!expandedRows[item.id] ? 'View variations & details' : 'Collapse'">
                        <ChevronDown v-if="!expandedRows[item.id]" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        <ChevronUp v-else class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                      </button>
                      <!-- Mobile Status Toggle -->
                      <div class="lg:hidden">
                        <button
                            @click="toggleStatus(item)"
                            class="relative inline-flex h-4 w-7 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-300 shadow-inner"
                            :class="item.status === 'Published' ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'"
                            :title="item.status === 'Published' ? 'Live – tap to hide' : 'Draft – tap to publish'"
                        >
                          <span class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-300"
                                :class="item.status === 'Published' ? 'translate-x-3' : 'translate-x-0'" />
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>

                <!-- Expanded Detail Row -->
                <tr v-if="expandedRows[item.id]" class="bg-gradient-to-b from-blue-50/30 to-transparent dark:from-blue-900/5">
                  <td colspan="9" class="px-4 sm:px-6 pb-5 pt-0 border-l-4 border-blue-400 dark:border-blue-600">
                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 p-4 sm:p-6 bg-white dark:bg-gray-800/70 rounded-2xl border border-blue-100 dark:border-blue-900/30 shadow-sm">

                      <!-- Image & Core Info -->
                      <div class="space-y-4">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Product Preview</p>
                        <div class="relative group overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                          <img :src="getFeatureImageUrl(item)" :alt="item.name"
                               class="w-full h-44 object-cover group-hover:scale-105 transition-transform duration-500" />
                          <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                          <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Brand</p>
                            <p class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate">{{ item.brand?.brand_name || 'Generic' }}</p>
                          </div>
                          <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                            <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Type</p>
                            <p class="text-xs font-bold text-gray-900 dark:text-gray-100 uppercase">{{ item.type }}</p>
                          </div>
                        </div>
                      </div>

                      <!-- Description -->
                      <div class="space-y-3">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Description</p>
                        <div v-if="item.short_description"
                             class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed bg-gray-50 dark:bg-gray-900 p-4 rounded-xl border border-gray-100 dark:border-gray-800"
                             v-html="item.short_description">
                        </div>
                        <div v-else class="text-sm text-gray-400 italic p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">No description.</div>
                        <!-- Quick links -->
                        <div class="flex flex-wrap gap-2 pt-1">
                          <Link :href="route('admin.products.edit', item.id)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all active:scale-95 shadow-sm">
                            <SquarePen class="w-3 h-3" /> Edit Product
                          </Link>
                          <button @click="cloneProduct(item.id)"
                                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl transition-all active:scale-95">
                            <LayoutTemplate class="w-3 h-3" /> Clone
                          </button>
                        </div>
                      </div>

                      <!-- Variations (if variable) -->
                      <div v-if="item.type === 'variable' && item.variations?.length" class="space-y-3 sm:col-span-2 lg:col-span-1">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Variations ({{ item.variations.length }})</p>
                        <div class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar pr-1">
                          <div v-for="variation in item.variations" :key="variation.id"
                               class="flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-900/60 rounded-xl border border-gray-100 dark:border-gray-800 hover:border-blue-200 dark:hover:border-blue-800 transition-colors">
                            <div class="flex items-center gap-2.5 min-w-0">
                              <!-- Variation image / fallback -->
                              <div class="relative w-10 h-10 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 flex-shrink-0 bg-gray-100 dark:bg-gray-800">
                                <img v-if="getVariationImageUrl(variation)"
                                     :src="getVariationImageUrl(variation)"
                                     class="w-full h-full object-cover"
                                     :alt="getVariationLabel(variation)"
                                     @error="$event.target.style.display = 'none'"
                                />
                                <div class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-gray-500">
                                  {{ getVariationInitials(variation) }}
                                </div>
                              </div>
                              <div class="min-w-0">
                                <div class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate flex items-center gap-1.5">
                                  {{ getVariationLabel(variation) || `#${variation.id}` }}
                                  <span v-if="variation.is_default" class="px-1.5 py-0.5 text-[9px] bg-orange-100 dark:bg-orange-900/30 text-orange-600 rounded-full font-bold">Default</span>
                                </div>
                                <div class="flex flex-wrap gap-1 mt-0.5">
                                  <template v-for="attr in variation.attributes" :key="attr.id">
                                    <span class="px-1.5 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded text-[9px] font-medium">
                                      {{ attr.value?.attribute?.name }}: {{ attr.value?.value }}
                                    </span>
                                  </template>
                                </div>
                              </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                              <div class="text-sm font-black text-gray-900 dark:text-gray-100 tabular-nums">৳{{ variation.price }}</div>
                              <div class="flex items-center gap-1 justify-end mt-0.5">
                                <div class="w-1.5 h-1.5 rounded-full"
                                     :class="(variation.inventory_stock?.available_quantity ?? 0) > 10 ? 'bg-green-500' : ((variation.inventory_stock?.available_quantity ?? 0) > 0 ? 'bg-amber-500' : 'bg-red-500')"></div>
                                <span class="text-xs font-bold text-gray-600 dark:text-gray-400 tabular-nums">
                                  {{ variation.inventory_stock?.available_quantity ?? 0 }}u
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Simple product placeholder for 3rd col -->
                      <div v-else class="hidden lg:block space-y-3">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Product Actions</p>
                        <div class="flex flex-col gap-2">
                          <Link :href="route('admin.products.edit', item.id)"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold rounded-xl transition-all active:scale-95 shadow-sm">
                            <SquarePen class="w-4 h-4" /> Edit Product
                          </Link>
                          <button @click="cloneProduct(item.id)"
                                  class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-bold rounded-xl transition-all active:scale-95">
                            <LayoutTemplate class="w-4 h-4" /> Duplicate
                          </button>
                          <button @click="openDeleteModal(item.id)"
                                  class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-red-50 hover:bg-red-100 dark:bg-red-900/10 text-red-600 text-sm font-bold rounded-xl transition-all active:scale-95">
                            <Trash2Icon class="w-4 h-4" /> Delete
                          </button>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- Empty State -->
              <tr v-if="!hasProducts && !loading">
                <td colspan="9" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center max-w-sm mx-auto">
                    <div class="w-20 h-20 bg-gray-50 dark:bg-gray-900 rounded-3xl flex items-center justify-center mb-5 shadow-inner">
                        <Package class="w-10 h-10 text-gray-300 dark:text-gray-600" />
                    </div>
                    <h3 class="text-xl font-black text-gray-800 dark:text-gray-200 mb-2">No products found</h3>
                    <p class="text-sm text-gray-400 font-medium leading-relaxed mb-6">No products match your current filters. Try adjusting or clearing your filters.</p>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                      <Link :href="route('admin.products.create')"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl text-sm font-bold shadow-md shadow-orange-500/20 transition-all active:scale-95">
                        <Plus class="w-4 h-4" />
                        Add Product
                      </Link>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
          </div>
        </div>
        <!-- ─── Pagination ─── -->
        <div class="px-4 sm:px-6 py-4 sm:py-5 bg-gray-50/60 dark:bg-gray-900/40 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-4">
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 order-2 sm:order-1">
              Showing
              <span class="font-bold text-gray-700 dark:text-gray-300">{{ from }}–{{ to }}</span>
              of
              <span class="font-bold text-orange-600">{{ total }}</span>
              products
            </p>

            <div class="flex items-center gap-1.5 order-1 sm:order-2">
              <!-- First -->
              <button @click="goToPage(1)" :disabled="currentPage === 1 || loading" class="pagination-btn group">
                <ChevronFirst class="w-3.5 h-3.5" />
              </button>
              <!-- Prev -->
              <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1 || loading" class="pagination-btn">
                <ChevronLeft class="w-3.5 h-3.5" />
              </button>

              <!-- Page Numbers -->
              <div class="flex items-center gap-1">
                  <template v-if="lastPage <= 7">
                    <button v-for="page in lastPage" :key="page" @click="goToPage(page)"
                            :class="[currentPage === page ? 'bg-orange-500 text-white shadow-md shadow-orange-500/20 font-black' : 'text-gray-500 dark:text-gray-400 hover:bg-white dark:hover:bg-gray-800 font-semibold']"
                            class="w-8 h-8 rounded-lg text-xs transition-all active:scale-90">
                      {{ page }}
                    </button>
                  </template>
                  <template v-else>
                      <button @click="goToPage(1)" :class="[currentPage === 1 ? 'bg-orange-500 text-white font-black' : 'text-gray-500 font-semibold']" class="w-8 h-8 rounded-lg text-xs transition-all active:scale-90">1</button>
                      <span v-if="currentPage > 3" class="text-gray-300 px-1">···</span>
                      <template v-for="page in lastPage" :key="page">
                        <button v-if="page >= currentPage - 1 && page <= currentPage + 1 && page !== 1 && page !== lastPage"
                                @click="goToPage(page)"
                                :class="[currentPage === page ? 'bg-orange-500 text-white shadow-md font-black' : 'text-gray-500 font-semibold']"
                                class="w-8 h-8 rounded-lg text-xs transition-all active:scale-90">
                          {{ page }}
                        </button>
                      </template>
                      <span v-if="currentPage < lastPage - 2" class="text-gray-300 px-1">···</span>
                      <button @click="goToPage(lastPage)" :class="[currentPage === lastPage ? 'bg-orange-500 text-white font-black' : 'text-gray-500 font-semibold']" class="w-8 h-8 rounded-lg text-xs transition-all active:scale-90">{{ lastPage }}</button>
                  </template>
              </div>

              <!-- Next -->
              <button @click="goToPage(currentPage + 1)" :disabled="currentPage === lastPage || loading" class="pagination-btn">
                <ChevronRight class="w-3.5 h-3.5" />
              </button>
              <!-- Last -->
              <button @click="goToPage(lastPage)" :disabled="currentPage === lastPage || loading" class="pagination-btn">
                <ChevronLast class="w-3.5 h-3.5" />
              </button>
            </div>
        </div>
          </div><!-- /Main Table Card -->
        </main>
      </div>

      <!-- Delete Confirmation Modal -->
      <DeleteModal
        v-if="productToDelete !== null"
        :item-id="productToDelete"
        item-name="product"
        route-name="admin.products.destroy"
        v-model:visible="showDeleteModal"
        @deleted="handleDeleteSuccess"
      />
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { toast } from "@steveyuowo/vue-hot-toast";
import DeleteModal from '@/Components/Modal/DeleteModal.vue';
import FilterSidebar from '@/Components/Product/FilterSidebar.vue';
import {
  ChevronDown,
  ChevronUp,
  Package,
  ChevronLeft,
  ChevronLast,
  ChevronFirst,
  ChevronRight,
  Trash2Icon,
  SquarePen,
  ArrowUpDown,
  ArrowUp,
  ArrowDown,
  Plus,
  LayoutTemplate
} from 'lucide-vue-next';

const props = defineProps({
  products: {
    type: Object,
    default: () => ({
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 50,
      total: 0
    })
  },
  categories: Array,
  brands: Array,
  filters: Object,
  stats: Object
});

// Professional data validation with computed properties
const safeProducts = computed(() => props.products || { data: [], current_page: 1, last_page: 1, per_page: 50, total: 0 });
const productData = computed(() => safeProducts.value.data || []);
const hasProducts = computed(() => productData.value.length > 0);

// Safe filters - exclude dangerous params that shouldn't persist across normal navigation
const safeFilters = computed(() => {
  const { only_trashed, with_trashed, page, ...safe } = props.filters || {};
  return safe;
});

// State for search, filters, and bulk delete
const searchQuery = ref(props.filters?.search || '');
const perPage = ref(props.filters?.per_page || 50);
const sortBy = ref(props.filters?.sort_by || 'created_at');
const sortOrder = ref(props.filters?.sort_order || 'desc');
const selectedProductIds = ref([]);
const expandedRows = ref({});
const loading = ref(false);
const showDeleteModal = ref(false);
const productToDelete = ref(null);
const isDeleting = ref(false);
const confirmationInput = ref('');

// Bulk operations state
const showBulkActions = ref(false);
const bulkPriceValue = ref(10); // Default 10% price adjustment
const bulkCategoryId = ref('');
const bulkActionsRef = ref(null);

// Mobile sort modal state & handlers
const showSortModal = ref(false);

const openSortModal = async () => {
  showSortModal.value = true;
  await nextTick();
  const modal = document.getElementById('sort-modal');
  if (modal) modal.focus();
};

const closeSortModal = () => {
  showSortModal.value = false;
};

const handleKeydown = (e) => {
  if (e.key === 'Escape' && showSortModal.value) closeSortModal();
};

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));

const handleSortAndClose = (field) => {
  handleSort(field);
  closeSortModal();
};

const toggleSortOrder = () => {
  sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  handleSearch();
};

const sortOptions = [
    { label: 'Date Added', value: 'created_at' },
    { label: 'Price', value: 'price' },
    { label: 'Name', value: 'name' },
];

// Professional computed properties for pagination with safe defaults
const paginationData = computed(() => safeProducts.value);
const currentPage = computed(() => safeProducts.value.current_page || 1);
const lastPage = computed(() => safeProducts.value.last_page || 1);
const from = computed(() => safeProducts.value.from || safeProducts.value.data?.length > 0 ? ((currentPage.value - 1) * (safeProducts.value.per_page || 50) + 1) : 0);
const to = computed(() => safeProducts.value.to || Math.min(currentPage.value * (safeProducts.value.per_page || 50), safeProducts.value.total || 0));
const total = computed(() => safeProducts.value.total || 0);
const links = computed(() => safeProducts.value.links || []);

// Methods
const openDeleteModal = (id) => {
  productToDelete.value = id;
  showDeleteModal.value = true;
};

const handleDeleteSuccess = () => {
  showDeleteModal.value = false;
  productToDelete.value = null;
};

const handleSort = (field) => {
    if (sortBy.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortOrder.value = 'desc';
    }
    handleSearch();
};

const toggleStatus = (product) => {
  const newStatus = product.status === 'Published' ? 'Unpublished' : 'Published';

  router.post(
    route('admin.products.quick-edit', product.id),
    {
      status: newStatus,
      is_free_delivery: product.is_free_delivery,
      remarks: product.remarks || null
    },
    {
      preserveState: true,
      onSuccess: () => {
        toast.success(`Product ${newStatus === 'Published' ? 'published' : 'unpublished'} successfully!`);
      },
      onError: (errors) => {
        toast.error('Failed to update status');
      }
    }
  );
};

const handleSearch = () => {
  loading.value = true;
  router.get(
    route('admin.products.index'),
    {
        ...safeFilters.value, // Use safe filters (excludes only_trashed, with_trashed)
        search: searchQuery.value,
        page: 1,
        per_page: perPage.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
    },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false;
      }
    }
  );
};

const handlePerPageChange = () => {
  loading.value = true;
  router.get(
    route('admin.products.index'),
    {
        ...safeFilters.value,
        search: searchQuery.value,
        page: 1,
        per_page: perPage.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
    },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false;
      }
    }
  );
};

const goToPage = (page) => {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return;

  loading.value = true;
  router.get(
    route('admin.products.index'),
    {
        ...safeFilters.value,
        search: searchQuery.value,
        page: page,
        per_page: perPage.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
    },
    {
      preserveState: true,
      onSuccess: () => {
        loading.value = false;
      }
    }
  );
};

const toggleExpanded = (id) => {
  expandedRows.value = {
    ...expandedRows.value,
    [id]: !expandedRows.value[id]
  };
};

const toggleSelectAll = () => {
  if (selectedProductIds.value.length === productData.value.length) {
    selectedProductIds.value = [];
  } else {
    selectedProductIds.value = productData.value.map(item => item.id);
  }
};

const isSelected = (id) => {
  return selectedProductIds.value.includes(id);
};

const toggleSelection = (id) => {
  const index = selectedProductIds.value.indexOf(id);
  if (index === -1) {
    selectedProductIds.value.push(id);
  } else {
    selectedProductIds.value.splice(index, 1);
  }
};

const deleteSelectedProducts = () => {
  if (selectedProductIds.value.length === 0) {
    toast.error('No products selected');
    return;
  }

  if (confirmationInput.value !== 'confirm') {
    toast.error('Please type "confirm" to delete selected products');
    return;
  }

  isDeleting.value = true;
  router.post(
    route('admin.products.bulk-delete'),
    { product_ids: selectedProductIds.value, confirmation: confirmationInput.value },
    {
      preserveState: true,
      onSuccess: () => {
        toast.success(`${selectedProductIds.value.length} products deleted successfully!`);
        selectedProductIds.value = [];
        confirmationInput.value = '';
        isDeleting.value = false;
      },
      onError: (errors) => {
        toast.error('Failed to delete selected products');
        Object.values(errors).forEach(error => toast.error(error));
        isDeleting.value = false;
      }
    }
  );
};

// Bulk Operations Methods
const toggleBulkActions = () => {
  showBulkActions.value = !showBulkActions.value;
};

const bulkUpdateStatus = (status) => {
  if (selectedProductIds.value.length === 0) {
    toast.error('No products selected');
    return;
  }

  router.post(
    route('admin.products.bulk-update-status'),
    {
      product_ids: selectedProductIds.value,
      status: status
    },
    {
      preserveState: true,
      onSuccess: () => {
        toast.success(`${selectedProductIds.value.length} products updated to ${status}`);
        selectedProductIds.value = [];
        showBulkActions.value = false;
      },
      onError: () => toast.error('Failed to update product status')
    }
  );
};

const bulkUpdatePrice = (type) => {
  if (selectedProductIds.value.length === 0) {
    toast.error('No products selected');
    return;
  }

  if (!bulkPriceValue.value || bulkPriceValue.value <= 0 || bulkPriceValue.value > 100) {
    toast.error('Please enter a valid percentage (1-100)');
    return;
  }

  router.post(
    route('admin.products.bulk-update-price'),
    {
      product_ids: selectedProductIds.value,
      price_type: type,
      price_value: bulkPriceValue.value
    },
    {
      preserveState: true,
      onSuccess: () => {
        const action = type === 'increase' ? 'increased' : 'decreased';
        toast.success(`Prices ${action} by ${bulkPriceValue.value}%`);
        selectedProductIds.value = [];
        showBulkActions.value = false;
      },
      onError: () => toast.error('Failed to update product prices')
    }
  );
};

const bulkAssignCategory = () => {
  if (selectedProductIds.value.length === 0) {
    toast.error('No products selected');
    return;
  }

  if (!bulkCategoryId.value) {
    toast.error('Please select a category');
    return;
  }

  router.post(
    route('admin.products.bulk-assign-category'),
    {
      product_ids: selectedProductIds.value,
      category_id: bulkCategoryId.value
    },
    {
      preserveState: true,
      onSuccess: () => {
        toast.success('Category assigned successfully');
        selectedProductIds.value = [];
        bulkCategoryId.value = '';
        showBulkActions.value = false;
      },
      onError: () => toast.error('Failed to assign category')
    }
  );
};

const cloneProduct = (productId) => {
  if (confirm('Clone this product? A copy will be created as draft.')) {
    router.post(
      route('admin.products.clone', productId),
      {},
      {
        preserveState: true,
        onSuccess: () => {
          toast.success('Product cloned successfully!');
        },
        onError: () => toast.error('Failed to clone product')
      }
    );
  }
};

// Close bulk actions dropdown when clicking outside
const handleClickOutside = (event) => {
  if (bulkActionsRef.value && !bulkActionsRef.value.contains(event.target)) {
    showBulkActions.value = false;
  }
};

onMounted(() => {
  window.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  window.removeEventListener('click', handleClickOutside);
});

// Helpers: default variation & compact variation preview
const getDefaultVariation = (item) => {
  if (!item || !item.variations || !item.variations.length) return null;
  return item.variations.find(v => v.is_default) || item.variations[0];
};

const getDisplayPrice = (item) => {
  if (!item) return 0;

  // For variable products prefer the default variation's price first (even if 0),
  // then prefer other positive variation prices, then any numeric fallback.
  if (item.type === 'variable' && item.variations && item.variations.length) {
    const def = getDefaultVariation(item);

    // If default variation has an explicit numeric price (including 0), use it.
    if (def && def.price !== undefined && def.price !== null) {
      const n = Number(def.price);
      if (!Number.isNaN(n)) return n;
    }

    // Next prefer first positive (>0) among other candidates
    const candidates = [
      ...(item.variations.map(v => v.price)),
      ...(item.variations.map(v => v.previous_price)),
      item.price,
      item.previous_price
    ];

    for (const c of candidates) {
      if (c !== undefined && c !== null) {
        const n = Number(c);
        if (!Number.isNaN(n) && n > 0) return n;
      }
    }

    // Fallback: any numeric value (including 0)
    for (const c of candidates) {
      if (c !== undefined && c !== null) {
        const n = Number(c);
        if (!Number.isNaN(n)) return n;
      }
    }

    return 0;
  }

  // Simple products: use product price or previous_price
  const p = item.price ?? item.previous_price;
  const n = Number(p);
  return (!Number.isNaN(n) && p !== null && p !== undefined) ? n : 0;
};

const getPreviousPrice = (item) => {
  if (!item) return null;
  if (item.type === 'variable' && item.variations && item.variations.length) {
    const def = getDefaultVariation(item);
    if (def && def.previous_price !== undefined && def.previous_price !== null) return Number(def.previous_price);
    // fallback to product previous price
    if (item.previous_price !== undefined && item.previous_price !== null) return Number(item.previous_price);
    return null;
  }
  if (item.previous_price !== undefined && item.previous_price !== null) return Number(item.previous_price);
  return null;
};

const formatCurrency = (val) => {
  if (val === null || val === undefined) return '0.00';
  const n = Number(val);
  if (Number.isNaN(n)) return '0.00';
  return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const getCompactVariations = (item, limit = 2) => {
  if (!item || !item.variations || !item.variations.length) return [];
  const arr = item.variations.slice(0, limit).map(v => {
    const attrs = (v.attributes || []).map(a => a.value?.value || a.value).join(' / ');
    const label = attrs || v.sku || v.variation_code || `#${v.id}`;
    return { label, isDefault: !!v.is_default };
  });
  if (item.variations.length > limit) {
    arr.push({ label: `+${item.variations.length - limit} more`, isMore: true });
  }
  return arr;
};

// Image helpers: normalize URLs and provide placeholder when missing
const normalizeImageUrl = (pathOrUrl, opts = { width: 400, height: 400 }) => {
  if (!pathOrUrl) return `/placeholder.svg?width=${opts.width}&height=${opts.height}`;
  if (typeof pathOrUrl !== 'string') return `/placeholder.svg?width=${opts.width}&height=${opts.height}`;
  if (pathOrUrl.startsWith('http') || pathOrUrl.startsWith('//')) return pathOrUrl;
  if (pathOrUrl.startsWith('/')) return pathOrUrl;
  return '/' + pathOrUrl;
};

const getFeatureImageUrl = (item) => {
  if (!item) return normalizeImageUrl(null);
  return normalizeImageUrl(item.feature_image_url ?? item.feature_image, { width: 480, height: 320 });
};

const getVariationImageUrl = (variation) => {
  if (!variation) return normalizeImageUrl(null, { width: 96, height: 96 });
  // Check for variation image first, then fallback to parent product image
  const variationImage = variation.image_path;
  if (variationImage) {
    return normalizeImageUrl(variationImage, { width: 96, height: 96 });
  }
  // Fallback to parent product feature image
  return normalizeImageUrl(variation.product?.feature_image, { width: 96, height: 96 });
};

const getVariationLabel = (variation) => {
  if (!variation) return '';

  // First try to create a label from attributes
  if (variation.attributes && variation.attributes.length) {
    const attrs = variation.attributes.map(attr => {
      return `${attr.value?.attribute?.name || 'Unknown'}: ${attr.value?.value || 'Unknown'}`;
    }).join(', ');
    if (attrs) return attrs;
  }

  // Fallback to SKU or variation code or ID
  return variation.sku || variation.variation_code || `Variation #${variation.id}`;
};

// Generate initials for variation placeholder (e.g., "BL" for Blue, Large)
const getVariationInitials = (variation) => {
  if (!variation) return '?';

  if (variation.attributes && variation.attributes.length > 0) {
    return variation.attributes
      .slice(0, 2) // Take first 2 attributes
      .map(attr => (attr.value?.value || 'X')[0].toUpperCase())
      .join('');
  }

  // Fallback to variation ID
  return `V${variation.id}`.slice(0, 2);
};
// Short description helpers
const stripHtml = (html) => {
  if (!html) return '';
  return html.replace(/<[^>]*>/g, '')?.trim();
};

const getShortDescription = (item, limit = 100) => {
  if (!item || !item.short_description) return '';
  const text = stripHtml(item.short_description);
  return text.length > limit ? text.slice(0, limit).trim() + '...' : text;
};

const formatDate = (isoDate) => {
  const date = new Date(isoDate);
  const now = new Date();
  const diffInMs = now - date; // Difference in milliseconds

  const seconds = Math.floor(diffInMs / 1000);
  const minutes = Math.floor(seconds / 60);
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);

  if (days > 0) {
    return `${days} day${days === 1 ? '' : 's'} ago`;
  } else if (hours > 0) {
    return `${hours} hour${hours === 1 ? '' : 's'} ago`;
  } else if (minutes > 0) {
    return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
  } else {
    return `${seconds} second${seconds === 1 ? '' : 's'} ago`;
  }
};

// Debounce search input
let searchTimeout = null;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    handleSearch();
  }, 500);
};
</script>

<style scoped>
/* ── Pagination Button ── */
.pagination-btn {
  @apply w-8 h-8 rounded-lg flex items-center justify-center
         bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
         text-gray-500 dark:text-gray-400
         hover:bg-gray-50 dark:hover:bg-gray-700
         disabled:opacity-30 disabled:cursor-not-allowed
         transition-all active:scale-90 shadow-sm;
}

/* Legacy alias kept for safety */
.pagination-button {
  @apply pagination-btn;
}

/* ── Scrollbar ── */
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
  height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
}

/* ── Product Image Hover Zoom ── */
.product-image-wrapper {
  position: relative;
  overflow: hidden;
  cursor: zoom-in;
}
.product-image {
  object-fit: cover;
  transition: transform 0.45s cubic-bezier(0.4, 0, 0.2, 1);
}
.product-image-wrapper:hover .product-image {
  transform: scale(1.2);
}

/* ── Lazy image fade-in ── */
img[loading="lazy"] {
  opacity: 0;
  animation: imgFadeIn 0.3s ease forwards;
}
@keyframes imgFadeIn {
  to { opacity: 1; }
}

/* ── Shadow inner helper ── */
.shadow-inner-lg {
  box-shadow: inset 0 2px 15px 0 rgba(0,0,0,0.025);
}
</style>

