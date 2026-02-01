<template>
  <div class="products-stock">
    <h1>Products Stock Data</h1>
    <div v-if="loading">Loading stock data...</div>
    <div v-else>
      <div v-for="product in productsStock" :key="product.product_id" class="product-card">
        <h2>{{ product.product_name }}</h2>
        <p>Total Stock: {{ product.total_stock }}</p>
        <p>Total Sold: {{ product.total_sold }}</p>
        <p>Initial Stock: {{ product.initial_stock }}</p>
        <p>Remaining Stock: {{ product.remaining_stock }}</p>
        <p>Stock Ratio: {{ product.stock_ratio }}%</p>

        <div v-if="product.variations.length">
          <h3>Variations</h3>
          <div v-for="variation in product.variations" :key="variation.variation_id" class="variation-card">
            <p><strong>ID:</strong> {{ variation.variation_id }}</p>
            <p><strong>Attributes:</strong>
              <span v-for="(value, key) in variation.attributes" :key="key">
                {{ key }}: {{ value }};
              </span>
            </p>
            <p>Current Stock: {{ variation.current_stock }}</p>
            <p>Sold Stock: {{ variation.sold_stock }}</p>
            <p>Initial Stock: {{ variation.initial_stock }}</p>
            <p>Remaining Stock: {{ variation.remaining_stock }}</p>
            <p>Sold Ratio: {{ variation.sold_ratio }}%</p>
            <p>Price: {{ variation.price }}</p>
            <img :src="variation.image" alt="Variation Image" class="variation-image" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ProductsStock',
  data() {
    return {
      productsStock: [],
      loading: true,
    };
  },
  created() {
    this.fetchStockData();
  },
  methods: {
    async fetchStockData() {
      try {
        const response = await axios.get('/api/products-stock');
        this.productsStock = response.data;
      } catch (error) {
        console.error('Error fetching stock data:', error);
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.products-stock {
  padding: 20px;
}

.product-card, .variation-card {
  border: 1px solid #ddd;
  padding: 15px;
  margin-bottom: 15px;
  border-radius: 4px;
  background: #fff;
}

.variation-image {
  max-width: 100%;
  height: auto;
  display: block;
  margin-top: 10px;
}
</style>
