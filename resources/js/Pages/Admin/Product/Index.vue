
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
            <div class="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
               <div>
                  <h1 class="text-2xl font-black text-gray-900 dark:text-gray-100 tracking-tight flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg">
                      <Package class="w-6 h-6 text-white" />
                    </div>
                    Catalog Inventory
                  </h1>
                  <p class="text-sm text-gray-600 dark:text-gray-400 font-medium mt-1.5 ml-13">Showing {{ from }}-{{ to }} of {{ total }} units</p>
               </div>

               <div class="flex items-center gap-3">
                  <div v-if="selectedProductIds.length > 0" class="flex items-center gap-2 px-3 py-1.5 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded-xl animate-in fade-in slide-in-from-right-4 duration-300">
                      <span class="text-xs font-bold text-red-600 uppercase tracking-tight">{{ selectedProductIds.length }} Selected</span>
                      <div class="h-4 w-px bg-red-200 dark:bg-red-800 mx-1"></div>
                      <input
                        v-model="confirmationInput"
                        type="text"
                        placeholder="Type 'confirm'"
                        class="w-24 px-2 py-0.5 text-[10px] bg-white dark:bg-gray-900 border-red-200 dark:border-red-800 rounded-lg focus:ring-red-500/20"
                      >
                      <button
                        @click="deleteSelectedProducts"
                        :disabled="confirmationInput !== 'confirm' || isDeleting"
                        class="text-[10px] font-black uppercase text-red-600 hover:text-red-700 disabled:opacity-30 transition-all"
                      >
                        {{ isDeleting ? '...' : 'Delete' }}
                      </button>
                  </div>

                  <Link
                    :href="route('admin.products.create')"
                    class="group flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg text-sm font-bold shadow-lg shadow-orange-500/30 hover:shadow-xl hover:shadow-orange-500/40 transition-all duration-300 active:scale-95"
                  >
                    <Plus class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" />
                    New Product
                  </Link>
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

        <!-- Polished Data Table -->
        <div class="overflow-x-auto custom-scrollbar">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-850">
              <tr>
                <th class="px-6 py-4 text-left">
                  <input type="checkbox"
                    class="w-4 h-4 rounded border-2 border-gray-400 dark:border-gray-500 text-orange-600 focus:ring-2 focus:ring-orange-500 shadow-sm transition-all cursor-pointer"
                    :checked="selectedProductIds.length === productData.length && hasProducts"
                    @change="toggleSelectAll" />
                </th>
                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Image</th>
                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider min-w-[200px]">Product Details</th>
                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider min-w-[150px]">Identity</th>
                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Financials</th>
                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Inventory</th>
                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                <th class="px-6 py-4 text-center text-[10px] font-extrabold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Actions</th>
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
                <!-- Main Row -->
                <tr class="group hover:bg-orange-50/30 dark:hover:bg-orange-900/5 transition-all duration-200">
                  <td class="px-6 py-5">
                    <input type="checkbox"
                      class="w-4 h-4 rounded border-2 border-gray-400 dark:border-gray-500 text-orange-600 focus:ring-2 focus:ring-orange-500 transition-all cursor-pointer"
                      :checked="isSelected(item.id)" @change="toggleSelection(item.id)" />
                  </td>
                  <td class="px-6 py-5">
                    <div class="relative w-16 h-16 shrink-0 group-hover:scale-105 transition-transform duration-500">
                        <img class="w-full h-full rounded-2xl object-cover bg-gray-50 dark:bg-gray-900 ring-1 ring-gray-200 dark:ring-gray-700 shadow-sm"
                          :src="getFeatureImageUrl(item)" :alt="item.name" />
                        <div v-if="item.is_daily_product" class="absolute -top-2 -right-2 bg-amber-400 text-white p-1 rounded-lg shadow-lg">
                            <Plus class="w-2 h-2 fill-current" />
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
                  <td class="px-6 py-5 whitespace-nowrap">
                    <div class="flex flex-col">
                      <div class="flex items-center gap-2">
                        <span class="text-base font-black text-gray-900 dark:text-gray-100">৳{{ formatCurrency(getDisplayPrice(item)) }}</span>
                        <span v-if="item.type === 'variable'" class="text-[10px] text-gray-500 font-bold uppercase">VARIABLE</span>
                      </div>

                      <span v-if="getPreviousPrice(item) !== null" class="text-xs text-gray-400 line-through font-medium">
                        ৳{{ formatCurrency(getPreviousPrice(item)) }}
                      </span>
                    </div>
                  </td>
                  <td class="px-6 py-5 whitespace-nowrap">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                             <div class="h-2 w-2 rounded-full shadow-sm" :class="item.stock > 10 ? 'bg-green-500' : (item.stock > 0 ? 'bg-amber-500' : 'bg-red-500')"></div>
                             <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ item.stock }} Units</span>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-tight" :class="item.stock > 10 ? 'text-green-600/70' : 'text-amber-600/70'">
                            {{ item.stock_status.replace('_', ' ') }}
                        </span>
                    </div>
                  </td>
                  <td class="px-6 py-5 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <button
                            @click="toggleStatus(item)"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-300 ease-in-out focus:outline-none focus:ring-4 focus:ring-blue-500/10 shadow-inner"
                            :class="item.status === 'Published' ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                        >
                          <span
                            aria-hidden="true"
                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xl ring-0 transition duration-300 ease-in-out"
                            :class="item.status === 'Published' ? 'translate-x-4' : 'translate-x-0'"
                          />
                        </button>
                        <span class="text-[10px] font-black uppercase tracking-widest" :class="item.status === 'Published' ? 'text-blue-600' : 'text-gray-400'">
                            {{ item.status }}
                        </span>
                    </div>
                  </td>
                  <td class="px-6 py-5 whitespace-nowrap">
                    <div class="flex justify-center items-center gap-1">
                      <Link :href="route('admin.products.edit', item.id)"
                           class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl transition-all active:scale-90">
                        <SquarePen class="w-4 h-4" />
                      </Link>
                      <button @click="openDeleteModal(item.id)"
                             class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all active:scale-90">
                        <Trash2Icon class="w-4 h-4" />
                      </button>
                      <button @click="toggleExpanded(item.id)"
                             class="p-2 text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all active:scale-90">
                        <ChevronDown v-if="!expandedRows[item.id]" class="w-4 h-4" />
                        <ChevronUp v-else class="w-4 h-4" />
                      </button>
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
                          <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2">Variations</p>
                          <div class="space-y-3">
                            <div v-for="variation in item.variations" :key="variation.id" class="flex items-center justify-between gap-4">
                              <div class="flex items-center gap-3">
                                <img :src="getVariationImageUrl(variation)" class="w-12 h-12 rounded-lg object-cover" />
                                <div class="min-w-0">
                                  <div class="text-sm font-black text-gray-900 dark:text-gray-100 truncate">
                                    {{ variation.sku || variation.variation_code || `Variation #${variation.id}` }}
                                    <span v-if="variation.is_default" class="ml-2 px-2 py-0.5 text-xs bg-blue-50 text-blue-600 rounded-full font-bold">Default</span>
                                  </div>
                                  <div class="text-xs text-gray-500 truncate">
                                    <template v-for="attr in variation.attributes" :key="attr.id">
                                      <span class="mr-2">{{ attr.value.attribute.name }}: {{ attr.value.value }}</span>
                                    </template>
                                  </div>
                                </div>
                              </div>
                              <div class="text-right">
                                <div class="text-base font-black text-gray-900 dark:text-gray-100">৳{{ variation.price }}</div>
                                <div class="text-xs text-gray-500">{{ variation.inventory_stock?.available_quantity ?? variation.stock ?? 0 }} Units</div>
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

// Helpers: default variation & compact variation preview
function getDefaultVariation(item) {
  if (!item || !item.variations || !item.variations.length) return null;
  return item.variations.find(v => v.is_default) || item.variations[0];
}

function getDisplayPrice(item) {
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
}

function getPreviousPrice(item) {
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
}

function formatCurrency(val) {
  if (val === null || val === undefined) return '0.00';
  const n = Number(val);
  if (Number.isNaN(n)) return '0.00';
  return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function getCompactVariations(item, limit = 2) {
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
}

// Image helpers: normalize URLs and provide placeholder when missing
function normalizeImageUrl(pathOrUrl, opts = { width: 400, height: 400 }) {
  if (!pathOrUrl) return `/placeholder.svg?width=${opts.width}&height=${opts.height}`;
  if (typeof pathOrUrl !== 'string') return `/placeholder.svg?width=${opts.width}&height=${opts.height}`;
  if (pathOrUrl.startsWith('http') || pathOrUrl.startsWith('//')) return pathOrUrl;
  if (pathOrUrl.startsWith('/')) return pathOrUrl;
  return '/' + pathOrUrl;
}

function getFeatureImageUrl(item) {
  if (!item) return normalizeImageUrl(null);
  return normalizeImageUrl(item.feature_image_url ?? item.feature_image, { width: 480, height: 320 });
}

function getVariationImageUrl(variation) {
  if (!variation) return normalizeImageUrl(null, { width: 96, height: 96 });
  return normalizeImageUrl(variation.image_url ?? variation.image_path, { width: 96, height: 96 });
}

// Short description helpers
function stripHtml(html) {
  if (!html) return '';
  return html.replace(/<[^>]*>/g, '')?.trim();
}

function getShortDescription(item, limit = 100) {
  if (!item || !item.short_description) return '';
  const text = stripHtml(item.short_description);
  return text.length > limit ? text.slice(0, limit).trim() + '...' : text;
}

function formatDate(isoDate) {
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
}

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
</style>

