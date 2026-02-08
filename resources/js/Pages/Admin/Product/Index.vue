
<template>
  <Head title="Products Management" />
  <AdminLayout>
    <!-- Amazon Style Layout -->
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col lg:flex-row gap-8">
        <!-- Left Sidebar: Filters -->
        <aside class="w-full lg:w-[280px] shrink-0">
          <FilterSidebar
            :filters="filters"
            :categories="categories"
            :brands="brands"
          />
        </aside>

        <!-- Right Content: Stats & Table -->
        <main class="flex-1 min-w-0 space-y-6">
          <!-- Compact Stats Bar -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
             <div v-for="(val, label) in stats" :key="label"
                  class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-850 p-5 rounded-xl border-2 border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-lg hover:border-orange-200 dark:hover:border-orange-900/50 transition-all duration-300 group">
                <p class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ label.replace('_', ' ') }}</p>
                <div class="flex items-end justify-between">
                    <p class="text-3xl font-black text-gray-900 dark:text-gray-50 leading-none">{{ val }}</p>
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 flex items-center justify-center text-orange-600 dark:text-orange-400 opacity-0 group-hover:opacity-100 transition-opacity shadow-sm">
                        <ArrowUp class="w-5 h-5" />
                    </div>
                </div>
             </div>
          </div>

          <!-- Main Actions & Search (Top of Table) -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
            <div class="p-3 sm:p-4 lg:p-6 border-b border-gray-100 dark:border-gray-700">
              <!-- Mobile-first header layout -->
               <div class="space-y-3 sm:space-y-2">
                  <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div class="flex-1">
                      <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-gray-100 tracking-tight flex items-center gap-2 sm:gap-3">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg">
                          <Package class="w-4 h-4 sm:w-6 sm:h-6 text-white" />
                        </div>
                        <span class="text-base sm:text-2xl">📦 Product Management</span>
                      </h1>
                      <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 font-medium mt-1 sm:mt-1.5 ml-10 sm:ml-13">💡 <strong>Quick Guide:</strong> Manage products easily • {{ from }}-{{ to }} of {{ total }} items</p>
                    </div>
                    <!-- Mobile New Product Button -->
                    <div class="flex-shrink-0">
                      <Link
                        :href="route('admin.products.create')"
                        class="group flex items-center gap-2 px-3 sm:px-5 py-2 sm:py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg text-sm font-bold shadow-lg shadow-orange-500/30 hover:shadow-xl hover:shadow-orange-500/40 transition-all duration-300 active:scale-95 touch-manipulation"
                        title="Click to add a new product to your store"
                      >
                        <Plus class="w-4 h-4 sm:w-5 sm:h-5 group-hover:rotate-90 transition-transform duration-300" />
                        <span class="hidden xs:inline">➤ Add New Product</span>
                        <span class="xs:hidden">Add</span>
                      </Link>
                    </div>
                  </div>
                  <!-- Status indicators - responsive layout -->
                  <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-xs ml-10 sm:ml-13">
                    <span class="flex items-center gap-1 text-green-600">
                      <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                      ✅ Published = Live on website
                    </span>
                    <span class="flex items-center gap-1 text-gray-500">
                      <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                      ⏸️ Draft = Hidden from customers
                    </span>
                    <span class="text-blue-600">
                      🔄 Click arrows to see variations
                    </span>
                  </div>
               </div>

               <div v-if="selectedProductIds.length > 0" class="flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/10 dark:to-orange-800/10 border border-orange-200 dark:border-orange-900/30 rounded-xl animate-in fade-in slide-in-from-right-4 duration-300 shadow-lg">
                  <span class="text-xs font-black text-orange-600 uppercase tracking-tight">{{ selectedProductIds.length }} Selected</span>
                  <div class="h-4 w-px bg-orange-300 dark:bg-orange-800 mx-1"></div>

                  <!-- Bulk Actions Dropdown -->
                  <div class="relative" ref="bulkActionsRef">
                    <button @click="toggleBulkActions" class="flex items-center gap-1.5 px-3 py-1 bg-white dark:bg-gray-900 border border-orange-200 dark:border-orange-800 rounded-lg text-[11px] font-black text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-all">
                      <span>⚙️ Bulk Actions</span>
                      <ChevronDown class="w-3 h-3" :class="{ 'rotate-180': showBulkActions }" />
                    </button>

                    <!-- Dropdown Menu -->
                    <div v-if="showBulkActions" class="absolute left-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden">
                      <!-- Status Update -->
                      <div class="p-3 border-b border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-gray-400 uppercase mb-2">Update Status</p>
                        <div class="flex gap-2">
                          <button @click="bulkUpdateStatus('Published')" class="flex-1 px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg text-xs font-bold transition-all">
                            ✅ Publish
                          </button>
                          <button @click="bulkUpdateStatus('Unpublished')" class="flex-1 px-3 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-lg text-xs font-bold transition-all">
                            ⏸️ Unpublish
                          </button>
                        </div>
                      </div>

                      <!-- Price Update -->
                      <div class="p-3 border-b border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-gray-400 uppercase mb-2">Adjust Prices</p>
                        <div class="flex gap-2 mb-2">
                          <input v-model="bulkPriceValue" type="number" min="0" max="100" placeholder="%" class="w-20 px-2 py-1 text-xs border rounded-lg" />
                          <button @click="bulkUpdatePrice('increase')" class="flex-1 px-2 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-bold">
                            📈 Increase
                          </button>
                          <button @click="bulkUpdatePrice('decrease')" class="flex-1 px-2 py-1.5 bg-orange-50 hover:bg-orange-100 text-orange-700 rounded-lg text-xs font-bold">
                            📉 Decrease
                          </button>
                        </div>
                      </div>

                      <!-- Category Assignment -->
                      <div class="p-3 border-b border-gray-100 dark:border-gray-700">
                        <p class="text-[10px] font-black text-gray-400 uppercase mb-2">Assign Category</p>
                        <select v-model="bulkCategoryId" class="w-full px-3 py-1.5 text-xs border rounded-lg">
                          <option value="">Select Category...</option>
                          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <button @click="bulkAssignCategory" :disabled="!bulkCategoryId" class="w-full mt-2 px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg text-xs font-bold disabled:opacity-30">
                          📂 Assign Now
                        </button>
                      </div>

                      <!-- Delete -->
                      <div class="p-3 bg-red-50/50 dark:bg-red-900/10">
                        <input v-model="confirmationInput" type="text" placeholder="Type 'confirm' to delete" class="w-full px-2 py-1 text-[10px] bg-white dark:bg-gray-900 border-red-200 dark:border-red-800 rounded-lg mb-2" />
                        <button @click="deleteSelectedProducts" :disabled="confirmationInput !== 'confirm' || isDeleting" class="w-full px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-black disabled:opacity-30 transition-all">
                          {{ isDeleting ? 'Deleting...' : '🗑️ Delete Selected' }}
                        </button>
                      </div>
                    </div>
                  </div>
               </div>
            </div>

            <!-- Sorting & Per Page Header -->
            <div class="px-4 py-3 bg-gray-50/50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4">
                <div class="hidden md:flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Sort By:</span>
                        <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg p-1 border-2 border-gray-200 dark:border-gray-700 shadow-sm">
                            <button
                                v-for="opt in sortOptions" :key="opt.value"
                                @click="handleSort(opt.value)"
                                :class="[sortBy === opt.value ? 'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-md' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700']"
                                class="px-4 py-1.5 rounded-md text-xs font-bold transition-all flex items-center gap-1.5"
                            >
                                {{ opt.label }}
                                <template v-if="sortBy === opt.value">
                                    <ArrowUp v-if="sortOrder === 'asc'" class="w-3 h-3" />
                                    <ArrowDown v-else class="w-3 h-3" />
                                </template>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile: Sort button (opens bottom-sheet) -->
                <div class="md:hidden flex items-center gap-2">
                  <button @click="openSortModal" class="flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm font-bold shadow-sm">
                    <ArrowUpDown class="w-4 h-4" />
                    <span>Sort</span>
                  </button>
                </div>

                <!-- Mobile Sort Bottom-sheet -->
                <div v-if="showSortModal" class="fixed inset-0 z-50 flex items-end md:hidden">
                  <div class="absolute inset-0 bg-black/40" @click="closeSortModal"></div>
                  <div id="sort-modal" tabindex="-1" role="dialog" aria-modal="true" class="relative w-full bg-white rounded-t-3xl p-6 max-h-[80vh] overflow-y-auto transform transition-transform duration-300">
                    <div class="flex items-center justify-between mb-4">
                      <h3 class="text-lg font-black">Sort</h3>
                      <button @click="closeSortModal" class="text-gray-500 font-bold">Close</button>
                    </div>

                    <div class="space-y-3">
                      <button v-for="opt in sortOptions" :key="opt.value" @click="handleSortAndClose(opt.value)"
                              :class="[sortBy === opt.value ? 'bg-blue-600 text-white' : 'bg-gray-50 text-gray-700']"
                              class="w-full text-left px-4 py-3 rounded-lg font-bold flex items-center justify-between">
                        <span>{{ opt.label }}</span>
                        <div v-if="sortBy === opt.value">
                          <ArrowUp v-if="sortOrder === 'asc'" class="w-4 h-4" />
                          <ArrowDown v-else class="w-4 h-4" />
                        </div>
                      </button>
                    </div>

                    <div class="mt-4">
                      <button @click="toggleSortOrder" class="w-full px-4 py-3 rounded-lg bg-gray-100 font-bold">
                        Toggle order: <span class="ml-2 font-black">{{ sortOrder === 'asc' ? 'Ascending' : 'Descending' }}</span>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Display:</span>
                    <select
                        v-model="perPage"
                        @change="handlePerPageChange"
                        class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-600 rounded-lg px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all shadow-sm hover:border-orange-300"
                    >
                        <option value="25">25 Per Page</option>
                        <option value="50">50 Per Page</option>
                        <option value="100">100 Per Page</option>
                    </select>
                </div>
            </div>

        <!-- Responsive Data Table -->
        <div class="overflow-x-auto custom-scrollbar">
          <div class="inline-block min-w-full align-middle">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-850">
                <tr>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-[9px] sm:text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider" title="Select products for bulk actions">
                    <span class="hidden sm:inline">☑️ Select</span>
                    <span class="sm:hidden">☑️</span>
                  </th>
                  <th class="px-2 sm:px-6 py-3 sm:py-4 text-left text-[9px] sm:text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider" title="Product photo">
                    <span class="hidden sm:inline">🖼️ Image</span>
                    <span class="sm:hidden">🖼️</span>
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-[9px] sm:text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider min-w-[160px] sm:min-w-[200px]" title="Product name and description">
                    <span class="hidden sm:inline">📦 Product Details</span>
                    <span class="sm:hidden">📦 Product</span>
                  </th>
                  <th class="hidden md:table-cell px-6 py-4 text-left text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider min-w-[150px]" title="SKU and barcode information">🏷️ Identity</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-[9px] sm:text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider" title="Pricing information">
                    <span class="hidden sm:inline">💰 Price</span>
                    <span class="sm:hidden">💰</span>
                  </th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-left text-[9px] sm:text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider" title="Stock levels and availability">
                    <span class="hidden sm:inline">📊 Stock</span>
                    <span class="sm:hidden">📊</span>
                  </th>
                  <th class="hidden xl:table-cell px-6 py-4 text-left text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider" title="Sales performance analytics">📈 Analytics</th>
                  <th class="hidden lg:table-cell px-6 py-4 text-left text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider" title="Published or draft status">🚦 Status</th>
                  <th class="px-3 sm:px-6 py-3 sm:py-4 text-center text-[9px] sm:text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider" title="Edit, delete, or view details">
                    <span class="hidden sm:inline">⚙️ Actions</span>
                    <span class="sm:hidden">⚙️</span>
                  </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <!-- Skeleton Loader -->
            <template v-if="loading">
              <tr v-for="i in perPage" :key="`skeleton-${i}`" class="animate-pulse">
                <td v-for="j in 8" :key="j" class="px-6 py-6">
                    <div class="h-4 bg-gray-100 dark:bg-gray-700 rounded-full w-full"></div>
                </td>
              </tr>
            </template>

            <!-- Actual Data -->
            <template v-else>
              <template v-for="(item, index) in productData" :key="item.id || `product-${index}`">
                <!-- Mobile-Responsive Main Row -->
                <tr class="group hover:bg-orange-50/30 dark:hover:bg-orange-900/5 transition-all duration-200">
                  <td class="px-3 sm:px-6 py-4 sm:py-5">
                    <input type="checkbox"
                      class="w-4 h-4 rounded border-2 border-gray-400 dark:border-gray-500 text-orange-600 focus:ring-2 focus:ring-orange-500 transition-all cursor-pointer touch-manipulation"
                      :checked="isSelected(item.id)" @change="toggleSelection(item.id)" />
                  </td>
                  <td class="px-2 sm:px-6 py-4 sm:py-5">
                    <div class="relative w-12 h-12 sm:w-16 sm:h-16 shrink-0 group">
                        <!-- Image with hover zoom and lazy loading -->
                        <div class="product-image-wrapper w-full h-full rounded-xl sm:rounded-2xl overflow-hidden bg-gray-50 dark:bg-gray-900 ring-1 ring-gray-200 dark:ring-gray-700 shadow-sm">
                          <img class="product-image w-full h-full object-cover transition-transform duration-500 group-hover:scale-125"
                            :src="getFeatureImageUrl(item)"
                            :alt="item.name"
                            loading="lazy" />
                        </div>

                        <!-- Daily Product Badge -->
                        <div v-if="item.is_daily_product" class="absolute -top-2 -right-2 bg-amber-400 text-white p-1 rounded-lg shadow-lg" title="Daily Deal Product">
                            <Plus class="w-2 h-2 fill-current" />
                        </div>

                        <!-- Multiple Images Indicator -->
                        <div v-if="item.gallery_images && item.gallery_images.length > 0" class="absolute -bottom-1 -right-1 bg-blue-500 text-white px-1.5 py-0.5 rounded-md text-[8px] font-black shadow-lg" title="Has gallery images">
                          {{ item.gallery_images.length + 1 }} 📷
                        </div>
                    </div>
                  </td>
                  <td class="px-6 py-5">
                    <div class="flex flex-col gap-1.5 overflow-hidden">
                      <h3 class="font-bold text-gray-900 dark:text-gray-100 truncate hover:text-blue-600 transition-colors cursor-pointer" @click="toggleExpanded(item.id)">
                        {{ item.name }}
                      </h3>

                      <!-- Short description (truncated) -->
                      <div v-if="item.short_description" class="text-sm text-gray-500 mt-1">
                        {{ getShortDescription(item, 100) }}
                      </div>

                      <!-- Compact variation preview for variable products -->
                      <div v-if="item.type === 'variable' && item.variations?.length" class="text-sm text-gray-500 mt-1">
                        <template v-for="(v, idx) in getCompactVariations(item)" :key="`compact-${item.id}-${idx}`">
                          <span :class="[v.isDefault ? 'font-black text-gray-900' : 'text-gray-500']">{{ v.label }}</span>
                          <span v-if="idx < getCompactVariations(item).length - 1">, </span>
                        </template>
                      </div>

                      <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-tight shadow-sm"
                          :class="item.type === 'simple' ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-purple-50 text-purple-600 border border-purple-100'">
                          {{ item.type }}
                        </span>
                        <!-- Category removed per request: no need to show category in Product Details -->
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-5">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2 group/code">
                            <span class="text-[10px] font-bold text-gray-300 uppercase tracking-tighter w-8 group-hover/code:text-blue-400 transition-colors">SKU</span>
                            <code class="px-2 py-0.5 bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-400 rounded-md text-[11px] font-mono border border-gray-100 dark:border-gray-800">
                                {{ item.product_code || '---' }}
                            </code>
                        </div>
                        <div v-if="item.barcode" class="flex items-center gap-2 group/code">
                            <span class="text-[10px] font-bold text-gray-300 uppercase tracking-tighter w-8 group-hover/code:text-blue-400 transition-colors">BCODE</span>
                            <code class="px-2 py-0.5 bg-blue-50/50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-300 rounded-md text-[11px] font-mono border border-blue-100 dark:border-blue-900/30">
                                {{ item.barcode }}
                            </code>
                        </div>
                    </div>
                  </td>
                  <td class="px-3 sm:px-6 py-4 sm:py-5 whitespace-nowrap">
                    <div class="flex flex-col gap-1">
                      <div class="flex items-center gap-1 sm:gap-2">
                        <span class="text-sm sm:text-base font-black text-gray-900 dark:text-gray-100">৳{{ formatCurrency(getDisplayPrice(item)) }}</span>
                        <span v-if="item.type === 'variable'" class="text-[8px] sm:text-[10px] text-gray-500 font-bold uppercase hidden sm:inline">VAR</span>

                        <!-- Sale Badge if has previous_price -->
                        <span v-if="getPreviousPrice(item) !== null && getPreviousPrice(item) > getDisplayPrice(item)"
                              class="px-1.5 py-0.5 bg-gradient-to-r from-red-500 to-red-600 text-white text-[8px] font-black rounded-full shadow-lg animate-pulse">
                          🔥 SALE
                        </span>
                      </div>

                      <!-- Previous Price with discount percentage -->
                      <div v-if="getPreviousPrice(item) !== null" class="flex items-center gap-2">
                        <span class="text-[10px] sm:text-xs text-gray-400 line-through font-medium">
                          ৳{{ formatCurrency(getPreviousPrice(item)) }}
                        </span>
                        <span v-if="getPreviousPrice(item) > getDisplayPrice(item)" class="px-1.5 py-0.5 bg-green-100 text-green-700 text-[8px] font-black rounded">
                          -{{ Math.round(((getPreviousPrice(item) - getDisplayPrice(item)) / getPreviousPrice(item)) * 100) }}%
                        </span>
                      </div>
                    </div>
                  </td>
                  <td class="px-3 sm:px-6 py-4 sm:py-5">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-1 sm:gap-2">
                             <div class="h-2 w-2 rounded-full shadow-sm flex-shrink-0" :class="item.stock > 10 ? 'bg-green-500' : (item.stock > 0 ? 'bg-amber-500' : 'bg-red-500')"></div>
                             <span class="text-xs sm:text-sm font-bold text-gray-700 dark:text-gray-300 truncate">
                               <span class="hidden sm:inline">📊 </span>{{ item.stock }} <span class="hidden sm:inline">Units</span><span class="sm:hidden">u</span>
                             </span>
                        </div>
                        <span class="text-[8px] sm:text-[10px] font-bold uppercase tracking-tight truncate" :class="item.stock > 10 ? 'text-green-600/70' : (item.stock > 0 ? 'text-amber-600/70' : 'text-red-600/70')">
                            <span class="hidden sm:inline">{{ item.stock > 10 ? '✅ GOOD STOCK' : (item.stock > 0 ? '⚠️ RUNNING LOW' : '🚨 OUT OF STOCK') }}</span>
                            <span class="sm:hidden">{{ item.stock > 10 ? '✅ GOOD' : (item.stock > 0 ? '⚠️ LOW' : '🚨 OUT') }}</span>
                        </span>
                        <!-- Show variation breakdown for variable products - Mobile Responsive -->
                        <div v-if="item.type === 'variable' && item.variations?.length" class="mt-1 text-[8px] sm:text-[9px] text-gray-500 space-y-0.5">
                          <div class="flex items-center gap-1 text-blue-600">
                            <span>🔄</span>
                            <span class="font-semibold truncate">{{ item.variations.length }} <span class="hidden sm:inline">variations</span><span class="sm:hidden">var</span>:</span>
                          </div>
                          <template v-for="(variation, idx) in item.variations.slice(0, item.variations.length > 6 ? 2 : 3)" :key="variation.id">
                            <div class="flex items-center justify-between px-1.5 sm:px-2 py-0.5 bg-gray-50 dark:bg-gray-800 rounded text-[7px] sm:text-[8px]">
                              <span class="truncate max-w-[60px] sm:max-w-[80px]" :title="getVariationLabel(variation)">
                                {{ getVariationLabel(variation) }}
                              </span>
                              <span class="font-semibold text-gray-700 dark:text-gray-300 ml-1 flex-shrink-0">
                                {{ variation.inventory_stock?.available_quantity ?? 0 }}u
                              </span>
                            </div>
                          </template>
                          <div v-if="item.variations.length > (item.variations.length > 6 ? 2 : 3)" class="text-[7px] sm:text-[8px] text-blue-600 font-semibold px-1.5 sm:px-2">
                            <span class="hidden sm:inline">👁️ +{{ item.variations.length - 3 }} more (click ▼ to see all)</span>
                            <span class="sm:hidden">👁️ +{{ item.variations.length - 2 }} more</span>
                          </div>
                        </div>
                    </div>
                  </td>
                  <!-- Analytics Column (XL screens only) -->
                  <td class="hidden xl:table-cell px-6 py-5">
                    <div class="space-y-2">
                      <!-- Sales Count -->
                      <div class="flex items-center justify-between gap-2">
                        <span class="text-[9px] font-bold text-gray-400 uppercase">7d Sales</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                              :class="item.sales_count_7_days >= 10 ? 'bg-green-100 text-green-700' : (item.sales_count_7_days >= 5 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600')">
                          {{ item.sales_count_7_days || 0 }}
                        </span>
                      </div>
                      <div class="flex items-center justify-between gap-2">
                        <span class="text-[9px] font-bold text-gray-400 uppercase">30d Sales</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                              :class="item.sales_count_30_days >= 30 ? 'bg-green-100 text-green-700' : (item.sales_count_30_days >= 15 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600')">
                          {{ item.sales_count_30_days || 0 }}
                        </span>
                      </div>
                      <!-- Revenue -->
                      <div class="flex items-center justify-between gap-2">
                        <span class="text-[9px] font-bold text-gray-400 uppercase">Revenue</span>
                        <span class="text-[10px] font-black text-green-600">৳{{ formatCurrency(item.total_revenue || 0) }}</span>
                      </div>
                      <!-- Profit Margin -->
                      <div class="flex items-center justify-between gap-2">
                        <span class="text-[9px] font-bold text-gray-400 uppercase">Margin</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black"
                              :class="item.profit_margin >= 30 ? 'bg-green-100 text-green-700' : (item.profit_margin >= 15 ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700')">
                          {{ item.profit_margin || 0 }}%
                        </span>
                      </div>
                      <!-- Performance Badge -->
                      <div class="mt-2 pt-2 border-t border-gray-100">
                        <div class="flex items-center gap-1.5">
                          <template v-if="item.performance_score === 'fast'">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span class="text-[9px] font-black text-green-600 uppercase">🚀 Fast Moving</span>
                          </template>
                          <template v-else-if="item.performance_score === 'moderate'">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span class="text-[9px] font-black text-blue-600 uppercase">📊 Moderate</span>
                          </template>
                          <template v-else>
                            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                            <span class="text-[9px] font-black text-gray-500 uppercase">🐌 Slow Moving</span>
                          </template>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="hidden lg:table-cell px-6 py-5 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <button
                            @click="toggleStatus(item)"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-300 ease-in-out focus:outline-none focus:ring-4 focus:ring-blue-500/10 shadow-inner"
                            :class="item.status === 'Published' ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                            :title="item.status === 'Published' ? '✅ Product is live on website - click to hide' : '⏸️ Product is hidden - click to publish'"
                        >
                          <span
                            aria-hidden="true"
                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xl ring-0 transition duration-300 ease-in-out"
                            :class="item.status === 'Published' ? 'translate-x-4' : 'translate-x-0'"
                          />
                        </button>
                        <span class="text-[10px] font-black uppercase tracking-widest flex items-center gap-1" :class="item.status === 'Published' ? 'text-blue-600' : 'text-gray-400'">
                            {{ item.status === 'Published' ? '✅' : '⏸️' }}
                            {{ item.status }}
                        </span>
                    </div>
                  </td>
                  <td class="px-2 sm:px-6 py-4 sm:py-5 whitespace-nowrap">
                    <div class="flex justify-center items-center gap-0.5 sm:gap-1">
                      <!-- Mobile: Stack vertically for better touch -->
                      <div class="flex sm:flex-row flex-col sm:gap-1 gap-0.5">
                        <Link :href="route('admin.products.edit', item.id)"
                             class="p-1.5 sm:p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg sm:rounded-xl transition-all active:scale-90 touch-manipulation"
                             title="✏️ Edit this product">
                          <SquarePen class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        </Link>
                        <button @click="cloneProduct(item.id)"
                               class="p-1.5 sm:p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg sm:rounded-xl transition-all active:scale-90 touch-manipulation"
                               title="📋 Clone/Duplicate this product">
                          <LayoutTemplate class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        </button>
                        <button @click="openDeleteModal(item.id)"
                               class="p-1.5 sm:p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg sm:rounded-xl transition-all active:scale-90 touch-manipulation"
                               title="🗑️ Delete this product permanently">
                          <Trash2Icon class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        </button>
                        <button @click="toggleExpanded(item.id)"
                               class="p-1.5 sm:p-2 text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg sm:rounded-xl transition-all active:scale-90 touch-manipulation"
                               :title="!expandedRows[item.id] ? '👁️ View detailed info and variations' : '🙈 Hide detailed info'">
                          <ChevronDown v-if="!expandedRows[item.id]" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                          <ChevronUp v-else class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        </button>
                      </div>
                      <!-- Mobile Status Toggle - Show on mobile only -->
                      <div class="lg:hidden ml-1">
                        <button
                            @click="toggleStatus(item)"
                            class="relative inline-flex h-4 w-7 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500/10 shadow-inner touch-manipulation"
                            :class="item.status === 'Published' ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                            :title="item.status === 'Published' ? '✅ Live - tap to hide' : '⏸️ Hidden - tap to publish'"
                        >
                          <span
                            aria-hidden="true"
                            class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow-lg ring-0 transition duration-300 ease-in-out"
                            :class="item.status === 'Published' ? 'translate-x-3' : 'translate-x-0'"
                          />
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>

                <!-- Expanded Row for Details -->
                <tr v-if="expandedRows[item.id]">
                  <td colspan="8" class="px-8 py-0 bg-gray-50/30 dark:bg-gray-900/20 border-l-4 border-l-blue-500">
                    <div class="py-10 grid grid-cols-1 md:grid-cols-3 gap-12 bg-white dark:bg-gray-800/50 rounded-b-3xl mt-2 mb-6 p-8 shadow-inner-lg">
                      <!-- Quick View Image -->
                      <div class="space-y-4">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Visual Reference</p>
                        <div class="relative group">
                          <img :src="getFeatureImageUrl(item)" class="w-full h-48 object-cover rounded-3xl border border-gray-100 dark:border-gray-700 shadow-xl group-hover:rotate-1 transition-transform" />
                          <div class="absolute inset-0 bg-gradient-to-t from-gray-900/20 to-transparent rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        </div>
                      </div>
                      <!-- Technical Specs -->
                      <div class="space-y-6">
                        <div>
                          <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Core Identity</p>
                          <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-2xl border border-gray-100 dark:border-gray-800">
                              <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Brand</p>
                              <p class="text-xs font-black text-gray-900 dark:text-gray-100 truncate">{{ item.brand?.brand_name || 'Generic' }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded-2xl border border-gray-100 dark:border-gray-800">
                              <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Type</p>
                              <p class="text-xs font-black text-gray-900 dark:text-gray-100 truncate uppercase">{{ item.type }}</p>
                            </div>
                          </div>
                        </div>
                        <!-- Full short description in expanded view -->
                        <div v-if="item.short_description" class="mt-4 col-span-2 md:col-span-1">
                          <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Short Description</p>
                          <div class="text-sm text-gray-700" v-html="item.short_description"></div>
                        </div>
                        <div v-if="item.type === 'variable' && item.variations?.length" class="mt-4 col-span-2 md:col-span-1">
                          <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3">🔄 Variations & Stock</p>
                          <div class="space-y-3">
                            <div v-for="variation in item.variations" :key="variation.id" class="flex items-center justify-between gap-4 p-3 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900/50 dark:to-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
                              <div class="flex items-center gap-3">
                                <!-- Variation Image with Fallback -->
                                <div class="relative w-12 h-12 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600">
                                  <img
                                    v-if="getVariationImageUrl(variation)"
                                    :src="getVariationImageUrl(variation)"
                                    class="w-full h-full object-cover"
                                    :alt="getVariationLabel(variation)"
                                    @error="$event.target.style.display = 'none'; $event.target.nextElementSibling.style.display = 'flex'"
                                  />
                                  <!-- Fallback colored placeholder -->
                                  <div class="w-full h-full bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center text-blue-600 font-black text-xs" style="display: none;">
                                    {{ getVariationInitials(variation) }}
                                  </div>
                                </div>

                                <div class="min-w-0">
                                  <div class="text-sm font-black text-gray-900 dark:text-gray-100 truncate flex items-center gap-2">
                                    {{ getVariationLabel(variation) || `Variation #${variation.id}` }}
                                    <span v-if="variation.is_default" class="px-2 py-0.5 text-xs bg-blue-50 text-blue-600 rounded-full font-bold">⭐ Default</span>
                                  </div>
                                  <div class="text-xs text-gray-500 truncate">
                                    <template v-for="attr in variation.attributes" :key="attr.id">
                                      <span class="mr-2 px-1.5 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 rounded text-[10px] font-medium">
                                        {{ attr.value.attribute.name }}: {{ attr.value.value }}
                                      </span>
                                    </template>
                                  </div>
                                </div>
                              </div>
                              <div class="text-right">
                                <div class="text-base font-black text-gray-900 dark:text-gray-100">💰 ৳{{ variation.price }}</div>
                                <div class="flex items-center gap-1 justify-end mt-1">
                                  <div class="h-2 w-2 rounded-full" :class="(variation.inventory_stock?.available_quantity ?? 0) > 10 ? 'bg-green-500' : ((variation.inventory_stock?.available_quantity ?? 0) > 0 ? 'bg-amber-500' : 'bg-red-500')"></div>
                                  <span class="text-sm font-bold text-gray-700 dark:text-gray-300">
                                    📊 {{ variation.inventory_stock?.available_quantity ?? 0 }} Units
                                  </span>
                                </div>
                                <div class="text-[10px] font-bold uppercase tracking-tight mt-0.5" :class="(variation.inventory_stock?.available_quantity ?? 0) > 10 ? 'text-green-600/70' : ((variation.inventory_stock?.available_quantity ?? 0) > 0 ? 'text-amber-600/70' : 'text-red-600/70')">
                                  {{ (variation.inventory_stock?.available_quantity ?? 0) > 10 ? '✅ GOOD' : ((variation.inventory_stock?.available_quantity ?? 0) > 0 ? '⚠️ LOW' : '🚨 OUT') }}
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- Empty State -->
              <tr v-if="!hasProducts && !loading">
                <td colspan="8" class="px-6 py-24 text-center">
                  <div class="flex flex-col items-center max-w-xs mx-auto">
                    <div class="w-20 h-20 bg-gray-50 dark:bg-gray-900 rounded-full flex items-center justify-center mb-6 shadow-inner">
                        <Package class="w-10 h-10 text-gray-200 dark:text-gray-700" />
                    </div>
                    <h3 class="text-xl font-black text-gray-900 dark:text-gray-100 mb-2 tracking-tight">Product Cloud Empty</h3>
                    <p class="text-xs text-gray-500 font-medium leading-relaxed mb-8">No matching records were found in the current galaxy. Try broadening your filter horizons.</p>
                    <Link :href="route('admin.products.create')"
                          class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-sm font-black shadow-xl shadow-blue-500/20 transition-all active:scale-95">
                      <Plus class="w-4 h-4" />
                      Add Initial Product
                    </Link>
                  </div>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
          </div>
        </div>

        <!-- Pagination Controls -->
        <!-- Polished Pagination -->
        <div class="px-6 py-6 bg-gray-50/50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-6">
            <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">
                    Inventory Overview: showing <span class="text-blue-600 dark:text-blue-400">{{ from }}-{{ to }}</span> of <span class="text-blue-600 dark:text-blue-400">{{ total }}</span> units
                </div>

                <div class="flex items-center gap-2">
                  <button @click="goToPage(1)"
                          :disabled="currentPage === 1 || loading"
                          class="pagination-button group"
                  >
                    <ChevronFirst class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" />
                  </button>
                  <button @click="goToPage(currentPage - 1)"
                          :disabled="currentPage === 1 || loading"
                          class="pagination-button"
                  >
                    <ChevronLeft class="w-4 h-4" />
                  </button>

                  <div class="flex items-center gap-1 mx-2">
                      <template v-if="lastPage <= 5">
                        <button v-for="page in lastPage" :key="page"
                                @click="goToPage(page)"
                                :class="[currentPage === page ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20' : 'text-gray-500 hover:bg-white dark:hover:bg-gray-800']"
                                class="w-8 h-8 rounded-lg text-xs font-black transition-all"
                        >
                          {{ page }}
                        </button>
                      </template>
                      <template v-else>
                          <button @click="goToPage(1)" :class="[currentPage === 1 ? 'bg-blue-600 text-white' : 'text-gray-500']" class="w-8 h-8 rounded-lg text-xs font-black transition-all">1</button>
                          <span v-if="currentPage > 3" class="text-gray-300">...</span>

                          <template v-for="page in lastPage" :key="page">
                            <button v-if="page >= currentPage - 1 && page <= currentPage + 1 && page !== 1 && page !== lastPage"
                                    @click="goToPage(page)"
                                    :class="[currentPage === page ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-500']"
                                    class="w-8 h-8 rounded-lg text-xs font-black transition-all"
                            >
                              {{ page }}
                            </button>
                          </template>

                          <span v-if="currentPage < lastPage - 2" class="text-gray-300">...</span>
                          <button @click="goToPage(lastPage)" :class="[currentPage === lastPage ? 'bg-blue-600 text-white' : 'text-gray-500']" class="w-8 h-8 rounded-lg text-xs font-black transition-all">{{ lastPage }}</button>
                      </template>
                  </div>

                  <button @click="goToPage(currentPage + 1)"
                          :disabled="currentPage === lastPage || loading"
                          class="pagination-button"
                  >
                    <ChevronRight class="w-4 h-4" />
                  </button>
                  <button @click="goToPage(lastPage)"
                          :disabled="currentPage === lastPage || loading"
                          class="pagination-button group"
                  >
                    <ChevronLast class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
                  </button>
                </div>
            </div>
          </div>
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
  ChevronRight,
  ChevronUp,
  Search,
  Package,
  ChevronLeft,
  ChevronLast,
  ChevronFirst,
  Trash2Icon,
  SquarePen,
  PencilIcon,
  ArrowUpDown,
  ArrowUp,
  ArrowDown,
  LayoutGrid,
  List,
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
.pagination-button {
  @apply w-8 h-8 rounded-lg flex items-center justify-center bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm active:scale-90;
}

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

.shadow-inner-lg {
    box-shadow: inset 0 2px 15px 0 rgba(0, 0, 0, 0.02);
}

/* Product Image Hover Zoom */
.product-image-wrapper {
  position: relative;
  overflow: hidden;
  cursor: zoom-in;
}

.product-image {
  object-fit: cover;
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.product-image-wrapper:hover .product-image {
  transform: scale(1.25);
}

/* Smooth loading state for lazy images */
.product-image[loading="lazy"] {
  opacity: 0;
  animation: fadeIn 0.3s ease-in forwards;
}

@keyframes fadeIn {
  to {
    opacity: 1;
  }
}

/* Mobile Responsive Utilities */
@media (max-width: 640px) {
  .touch-manipulation {
    touch-action: manipulation;
  }

  .xs\:hidden {
    display: none;
  }

  .xs\:inline {
    display: inline;
  }
}

@media (min-width: 400px) {
  .xs\:hidden {
    display: inline;
  }

  .xs\:inline {
    display: inline;
  }
}
</style>

