<script setup>
import { ref, watch, computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close"]);

// Track expanded categories
const expandedCategories = ref(new Set());

// Categories are already hierarchical with children from API
const parentCategories = computed(() => {
    // Filter only active root-level categories
    return props.categories.filter(
        (cat) => cat.status === "active" || cat.status === 1,
    );
});

// Toggle category expansion
const toggleCategory = (categoryId) => {
    if (expandedCategories.value.has(categoryId)) {
        expandedCategories.value.delete(categoryId);
    } else {
        expandedCategories.value.add(categoryId);
    }
    // Force reactivity
    expandedCategories.value = new Set(expandedCategories.value);
};

// Check if category is expanded
const isExpanded = (categoryId) => {
    return expandedCategories.value.has(categoryId);
};

// Close menu when clicking outside
const handleOverlayClick = () => {
    emit("close");
};

// Reset expanded state when menu closes
watch(
    () => props.isOpen,
    (newVal) => {
        if (!newVal) {
            expandedCategories.value = new Set();
        }
        // Prevent body scroll when menu is open
        if (newVal) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = "";
        }
    },
);
</script>

<template>
    <!-- Overlay -->
    <Transition name="fade">
        <div
            v-if="isOpen"
            class="fixed inset-0 bg-black/50 z-50 lg:hidden"
            @click="handleOverlayClick"
        ></div>
    </Transition>

    <!-- Sidebar Menu -->
    <Transition name="slide">
        <div
            v-if="isOpen"
            class="fixed top-0 left-0 h-full w-80 max-w-[85vw] bg-white z-50 shadow-xl overflow-hidden flex flex-col lg:hidden"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between p-4 border-b bg-gray-50"
            >
                <h2 class="text-lg font-bold text-gray-900">Menu</h2>
                <button
                    @click="emit('close')"
                    class="p-2 hover:bg-gray-200 rounded-full transition-colors"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Menu Content -->
            <div class="flex-1 overflow-y-auto">
                <!-- Quick Links -->
                <div class="p-4 border-b">
                    <Link
                        href="/"
                        class="flex items-center gap-3 py-2 text-gray-700 hover:text-primary"
                        @click="emit('close')"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                            />
                        </svg>
                        Home
                    </Link>
                </div>

                <!-- Categories -->
                <div class="py-2">
                    <template
                        v-for="category in parentCategories"
                        :key="category.id"
                    >
                        <MobileMenuItem
                            :category="category"
                            :expanded-categories="expandedCategories"
                            :level="0"
                            @toggle="toggleCategory"
                            @close="emit('close')"
                        />
                    </template>
                </div>
            </div>

            <!-- Footer Links -->
            <!-- <div class="p-4 border-t bg-gray-50">
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <Link
                        href="/offers"
                        class="text-primary font-medium"
                        @click="emit('close')"
                        >🎁 Offers</Link
                    >
                    <Link
                        href="/compare"
                        class="text-gray-600 hover:text-primary"
                        @click="emit('close')"
                        >Compare</Link
                    >
                    <Link
                        :href="route('pre-order-lead.form')"
                        class="text-gray-600 hover:text-primary"
                        @click="emit('close')"
                        >Pre-order</Link
                    >
                </div>
            </div> -->
        </div>
    </Transition>
</template>

<script>
// Recursive Mobile Menu Item Component
import { defineComponent, computed, h } from "vue";
import { Link } from "@inertiajs/vue3";

const MobileMenuItem = defineComponent({
    name: "MobileMenuItem",
    props: {
        category: {
            type: Object,
            required: true,
        },
        expandedCategories: {
            type: Set,
            default: () => new Set(),
        },
        level: {
            type: Number,
            default: 0,
        },
    },
    emits: ["toggle", "close"],
    setup(props, { emit }) {
        const children = computed(() => {
            return props.category.children || [];
        });

        const hasChildren = computed(() => children.value.length > 0);
        const isExpanded = computed(() =>
            props.expandedCategories.has(props.category.id),
        );

        const paddingLeft = computed(() => `${(props.level + 1) * 16}px`);

        const handleToggle = () => {
            emit("toggle", props.category.id);
        };

        const handleClose = () => {
            emit("close");
        };

        return {
            children,
            hasChildren,
            isExpanded,
            paddingLeft,
            handleToggle,
            handleClose,
        };
    },
    render() {
        const elements = [];

        // Category item
        elements.push(
            h(
                "div",
                {
                    class: "flex items-center border-b border-gray-100",
                    style: { paddingLeft: this.paddingLeft },
                },
                [
                    // Category Link
                    h(
                        Link,
                        {
                            href: `/category/${this.category.slug}`,
                            class: "flex-1 py-3 pr-2 text-gray-700 hover:text-primary transition-colors",
                            onClick: this.handleClose,
                        },
                        () => this.category.name,
                    ),

                    // Expand button if has children
                    this.hasChildren
                        ? h(
                              "button",
                              {
                                  class: `p-3 hover:bg-gray-100 transition-colors ${this.isExpanded ? "bg-gray-100" : ""}`,
                                  onClick: this.handleToggle,
                              },
                              [
                                  h(
                                      "svg",
                                      {
                                          xmlns: "http://www.w3.org/2000/svg",
                                          class: `h-5 w-5 transition-transform ${this.isExpanded ? "rotate-180" : ""}`,
                                          fill: "none",
                                          viewBox: "0 0 24 24",
                                          stroke: "currentColor",
                                      },
                                      [
                                          h("path", {
                                              "stroke-linecap": "round",
                                              "stroke-linejoin": "round",
                                              "stroke-width": "2",
                                              d: "M19 9l-7 7-7-7",
                                          }),
                                      ],
                                  ),
                              ],
                          )
                        : null,
                ],
            ),
        );

        // Children (if expanded)
        if (this.hasChildren && this.isExpanded) {
            elements.push(
                h(
                    "div",
                    {
                        class: "bg-gray-50",
                    },
                    this.children.map((child) =>
                        h(MobileMenuItem, {
                            key: child.id,
                            category: child,
                            expandedCategories: this.expandedCategories,
                            level: this.level + 1,
                            onToggle: (id) => this.$emit("toggle", id),
                            onClose: this.handleClose,
                        }),
                    ),
                ),
            );
        }

        return h("div", {}, elements);
    },
});

export default {
    components: {
        MobileMenuItem,
    },
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    transform: translateX(-100%);
}
</style>
