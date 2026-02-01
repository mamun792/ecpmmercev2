<script setup>
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

// Categories are already hierarchical with children from API
const parentCategories = computed(() => {
    // Filter only active categories at root level
    return props.categories.filter(
        (cat) => cat.status === "active" || cat.status === 1,
    );
});

// Check if category has children
const hasChildren = (category) => {
    return category.children && category.children.length > 0;
};

// Active category for hover state
const activeCategory = ref(null);
const activeSubCategory = ref(null);

const setActiveCategory = (catId) => {
    activeCategory.value = catId;
    activeSubCategory.value = null;
};

const setActiveSubCategory = (catId) => {
    activeSubCategory.value = catId;
};

const clearActive = () => {
    activeCategory.value = null;
    activeSubCategory.value = null;
};
</script>

<template>
    <nav
        class="hidden lg:block bg-white text-black border-t border-b border-gray-200"
        @mouseleave="clearActive"
    >
        <div class="container mx-auto px-4">
            <ul class="flex items-center gap-1">
                <li
                    v-for="category in parentCategories"
                    :key="category.id"
                    class="relative group"
                    @mouseenter="setActiveCategory(category.id)"
                >
                    <!-- Parent Category Link -->
                    <Link
                        :href="`/category/${category.slug}`"
                        class="flex items-center gap-1 px-2 py-3 text-sm font-medium text-black hover:bg-primary/10 hover:text-primary transition-colors"
                        :class="{
                            'text-primary': activeCategory === category.id,
                        }"
                    >
                        <!-- <span v-if="category.icon" v-html="category.icon" class="w-4 h-4"></span> -->
                        {{ category.name }}
                        <svg
                            v-if="hasChildren(category)"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 transition-transform"
                            :class="{
                                'rotate-180': activeCategory === category.id,
                            }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </Link>

                    <!-- First Level Dropdown -->
                    <div
                        v-if="hasChildren(category)"
                        class="absolute left-0 top-full bg-white shadow-lg rounded-b-lg border border-t-0 border-gray-200 min-w-[220px] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50"
                    >
                        <ul class="py-2">
                            <li
                                v-for="child in category.children"
                                :key="child.id"
                                class="relative group/sub"
                                @mouseenter="setActiveSubCategory(child.id)"
                            >
                                <Link
                                    :href="`/category/${child.slug}`"
                                    class="flex items-center justify-between px-4 py-2.5 text-sm text-gray-700 hover:bg-primary/10 hover:text-primary transition-colors"
                                    :class="{
                                        'bg-primary/10 text-primary':
                                            activeSubCategory === child.id &&
                                            hasChildren(child),
                                    }"
                                >
                                    <span class="flex items-center gap-2">
                                        <span
                                            v-if="child.icon"
                                            v-html="child.icon"
                                            class="w-4 h-4"
                                        ></span>
                                        {{ child.name }}
                                    </span>
                                    <svg
                                        v-if="hasChildren(child)"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5l7 7-7 7"
                                        />
                                    </svg>
                                </Link>

                                <!-- Second Level Dropdown (Recursive) -->
                                <template v-if="hasChildren(child)">
                                    <NestedDropdown
                                        :category="child"
                                        :level="2"
                                    />
                                </template>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</template>

<script>
// Recursive component for unlimited nested dropdowns
import { defineComponent, computed, h } from "vue";
import { Link } from "@inertiajs/vue3";

const NestedDropdown = defineComponent({
    name: "NestedDropdown",
    props: {
        category: {
            type: Object,
            required: true,
        },
        level: {
            type: Number,
            default: 2,
        },
    },
    setup(props) {
        const children = computed(() => {
            return props.category.children || [];
        });

        const hasChildren = (cat) => {
            return cat.children && cat.children.length > 0;
        };

        return { children, hasChildren };
    },
    render() {
        if (this.children.length === 0) return null;

        return h(
            "div",
            {
                class: "nested-dropdown absolute left-full top-0 bg-white shadow-lg rounded-lg border border-gray-200 min-w-[200px] opacity-0 invisible transition-all duration-200 z-50",
            },
            [
                h(
                    "ul",
                    { class: "py-2" },
                    this.children.map((child) =>
                        h(
                            "li",
                            {
                                key: child.id,
                                class: "relative group/nested",
                            },
                            [
                                h(
                                    Link,
                                    {
                                        href: `/category/${child.slug}`,
                                        class: "flex items-center justify-between px-4 py-2.5 text-sm text-gray-700 hover:bg-primary/10 hover:text-primary transition-colors",
                                    },
                                    () => [
                                        h(
                                            "span",
                                            {
                                                class: "flex items-center gap-2",
                                            },
                                            [
                                                child.icon
                                                    ? h("span", {
                                                          innerHTML: child.icon,
                                                          class: "w-4 h-4",
                                                      })
                                                    : null,
                                                child.name,
                                            ],
                                        ),
                                        this.hasChildren(child)
                                            ? h(
                                                  "svg",
                                                  {
                                                      xmlns: "http://www.w3.org/2000/svg",
                                                      class: "h-4 w-4",
                                                      fill: "none",
                                                      viewBox: "0 0 24 24",
                                                      stroke: "currentColor",
                                                  },
                                                  [
                                                      h("path", {
                                                          "stroke-linecap":
                                                              "round",
                                                          "stroke-linejoin":
                                                              "round",
                                                          "stroke-width": "2",
                                                          d: "M9 5l7 7-7 7",
                                                      }),
                                                  ],
                                              )
                                            : null,
                                    ],
                                ),
                                this.hasChildren(child)
                                    ? h(NestedDropdown, {
                                          category: child,
                                          level: this.level + 1,
                                      })
                                    : null,
                            ],
                        ),
                    ),
                ),
            ],
        );
    },
});

export default {
    components: {
        NestedDropdown,
    },
};
</script>

<style>
/* Global styles for nested dropdowns - needed for h() rendered content */
.group\/sub:hover > .nested-dropdown {
    opacity: 1 !important;
    visibility: visible !important;
}

.group\/nested:hover > .nested-dropdown {
    opacity: 1 !important;
    visibility: visible !important;
}
</style>
