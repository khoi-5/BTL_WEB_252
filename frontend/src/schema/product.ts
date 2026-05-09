export type ProductVersion = {
  version_id: number;
  product_id: number;
  product_name: string;
  brand: string | null;
  description: string | null;
  sku: string;
  version_name: string;
  format_type: "paperback" | "hardcover" | "ebook" | "special_edition";
  language: string;
  cover_type: string | null;
  edition: string | null;
  price: number;
  stock_quantity: number;
  image_url: string | null;
  version_status: "available" | "out_of_stock" | "hidden";
};

export type ProductPagination = {
  current_page: number;
  total_pages: number;
  total_items: number;
  limit: number;
};

export type ProductListResponse = {
  success: boolean;
  message?: string;
  data: {
    items: ProductVersion[];
    pagination: ProductPagination;
  };
};

export type ProductDetailResponse = {
  success: boolean;
  message?: string;
  data: ProductVersion;
};

export type Category = {
  category_id: number;
  category_name: string;
  parent_category_id: number | null;
};

export type FlashSaleProduct = {
  version_id: number;
  product_name: string;
  brand: string | null;
  version_name: string;
  image_url: string | null;
  original_price: number;
  sale_price: number;
  stock_allocated: number;
  stock_sold: number;
};

export type FlashSale = {
  flash_sale_id: number;
  flash_sale_name: string;
  flash_sale_description: string | null;
  flash_sale_image: string | null;
  flash_sale_start_time: string;
  flash_sale_end_time: string;
  sale_status: "active" | "upcoming" | "ended";
  products: FlashSaleProduct[];
};

export type ProductSection = {
  section_id: number;
  section_name: string;
  section_description: string | null;
  section_image: string | null;
  section_status: number;
  products: ProductVersion[];
};
