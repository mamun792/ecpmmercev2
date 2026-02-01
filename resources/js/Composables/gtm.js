// GTM Helper Functions

/**
 * Pushes event data to the GTM dataLayer
 * @param {Object} eventData - Event data to send
 */
export const pushToDataLayer = (eventData) => {
  if (window.dataLayer) {
    window.dataLayer.length = 0; // Clear old data
    window.dataLayer.push(eventData);
    //console.log("DataLayer updated:", window.dataLayer); // Log the new state
  } else {
    console.warn("GTM Data Layer is not initialized.");
  }
};

/**
* Tracks a Page View
* @param {string} pagePath - Current page path
* @param {string} pageTitle - Page title
*/
export const trackViewItem = (product, price) => {
  const ecommerceData = {
    event: "view_item",
    ecommerce: {
      currency: "BDT", // You can modify this based on your locale or preferences
      value: price, // Set the value dynamically from the product data
      items: [
        {
          item_id: product.id,
          item_name: product.name,
          price: price,
          item_brand: product.brand ? product.brand?.name : "No Brand", // Assuming you might have a brand property
          item_category: product.category ? product.category.name : "Uncategorized", // Assuming the category has a name
          quantity: 1 // You can dynamically set this as needed
        }
      ]
    }
  };

  pushToDataLayer(ecommerceData);
};










/**
* Tracks Add to Cart
* @param {Object} product - Product object
* @param {number} quantity - Quantity added
*/
export const trackAddToCart = (product, variation = null, quantity = 1) => {
  if (!product) return;

  const attributes = variation?.attributes || [];

  const itemVariant = attributes
    .map(attr => {
      const attrName = attr.value?.attribute?.name || "";
      const attrValue = attr.value?.value || "";
      return `${attrName}: ${attrValue}`;
    })
    .join(", ");

  const totalPrice =
    parseFloat(product.price) + parseFloat(variation?.price || 0); // if additive

  pushToDataLayer({
    event: "add_to_cart",
    ecommerce: {
      items: [
        {
          item_name: product.name,
          item_id: variation?.id || product.id,
          price: totalPrice,
          item_brand: product.brand?.name || "No Brand",
          item_category: product.category?.name || "",
          item_variant: itemVariant || "No Variant",
          quantity: quantity,
        },
      ],
    },
  });
};





/**
* Tracks Checkout
* @param {Array} cartItems - List of products in cart
* @param {number} totalPrice - Total cart value
*/
export const trackCheckout = (data) => {
  if (!data || !data.data || !Array.isArray(data.data.items)) return;

  //console.log("trackCheckout data:", data);

  const items = data.data.items; // Access the items array from the nested data structure

  const gtmData = {
    event: "begin_checkout",
    ecommerce: {
      currency: "BDT",
      value: parseFloat(data.data.total || 0), // Use total from the JSON
      items: items.map((item) => ({
        item_id: item.product_id, // Product ID
        item_name: item.product_name || "Unknown Product", // Product Name
        price: parseFloat(item.price || 0), // Product Price
        quantity: item.quantity || 1, // Product Quantity
        variant: item.variation_attributes
          ? item.variation_attributes
              .map((attr) => `${attr.name}: ${attr.value}`)
              .join(", ") // Format variation attributes
          : "", // Handle cases with no variations
      })),
    },
  };

  //console.log("GTAG Data:", gtmData);

  // Push the GTM data
  pushToDataLayer(gtmData);
};

/**
* Tracks Begin Checkout for Landing Pages
* @param {Object} pageData - The landing page data
*/
export const trackLandingBeginCheckout = (pageData) => {
  if (!pageData || !pageData.linked_products) return;

  const items = pageData.linked_products.filter(p => p.id).map(product => ({
    item_id: product.id,
    item_name: product.name,
    price: parseFloat(product.price || 0),
    item_brand: product.brand?.name || "No Brand",
    item_category: product.category?.name || "Uncategorized",
    quantity: 1
  }));

  const totalValue = items.reduce((sum, item) => sum + item.price, 0);

  const gtmData = {
    event: "begin_checkout",
    ecommerce: {
      currency: "BDT",
      value: totalValue,
      items: items
    }
  };

  pushToDataLayer(gtmData);
};




/**
* Tracks Purchase
* @param {Object} order - Order object containing details
*/
export const trackPurchase = (order) => {
  if (!order || !order.items || !Array.isArray(order.items)) return;

  console.log("trackPurchase data:", order);

  const gtmData = {
    event: "purchase",
    ecommerce: {
      transaction_id: order.order_number || `T_${Date.now()}`, // Use order_number from JSON
      currency: "BDT", // Currency from JSON
      value: parseFloat(order.total) || 0, // Total value from JSON
      shipping: parseFloat(order.shipping_cost) || 0, // Shipping cost from JSON
      tax: 0, // Tax not provided in JSON, default to 0
      items: order.items.map(item => ({
        item_id: item.product_id, // Product ID from JSON
        item_name: item.product.name || "Unknown Product", // Product name from JSON
        price: parseFloat(item.unit_price) || 0, // Unit price from JSON
        quantity: item.quantity || 1, // Quantity from JSON
        variant: item.product_variation && item.product_variation.attributes
          ? item.product_variation.attributes
              .map(attr => `${attr.value.attribute}: ${attr.value.value}`)
              .join(", ") // Format variation attributes
          : "", // Handle cases with no variations
        product_code: item.product.product_code || "No Code", // Product code from JSON
        total: parseFloat(item.subtotal) || 0, // Subtotal from JSON
        campaign_discount: parseFloat(item.discount_amount) || 0, // Discount amount from JSON
        coupon_discount: parseFloat(item.discount_total) || 0, // Coupon discount from JSON
        //original_price: parseFloat(item.product.previous_price) || parseFloat(item.unit_price) || 0, // Previous price or unit price
      })),
    },
    custom_data: {
      payment_method: order.payment_method || "cash", // Payment method from JSON
      shipping_method: order.shipping_method || "", // Shipping method from JSON (null in this case)
      discount_applied: parseFloat(order.discount_total) || 0, // Total discount from JSON
      coupon_code: order.coupon_code || "", // Coupon code (null in this case)
      delivery_charge: parseFloat(order.shipping_cost) || 0, // Shipping cost from JSON
      order_details: {
        paid_amount: 0, // Not provided in JSON, default to 0
        remaining_balance: parseFloat(order.total) || 0, // Assume total as remaining since unpaid
        order_type: "online", // Not provided, default to "online" based on context
        order_status: order.status || "pending", // Status from JSON
        delivery: order.shipping_address || "N/A", // Shipping address from JSON
      },
      customer_details: {
        user_identifier: order.session_id || "", // Session ID from JSON
        name: order.customer_name || "", // Customer name from JSON
        address: order.shipping_address || "", // Shipping address from JSON
        phone_number: order.customer_phone || "", // Customer phone from JSON
        email: order.customer_email || "", // Customer email from JSON
        select_area: order.area || "", // Area from JSON
        note: order.customer_notes || "", // Customer notes from JSON
      },
    },
  };

  console.log("GTM Purchase Data:", gtmData);

  pushToDataLayer(gtmData); // Send the data to the data layer
};



