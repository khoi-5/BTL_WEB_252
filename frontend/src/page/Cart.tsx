import { useEffect } from "react";
import { useCartStore } from "@/store/cartStore";
import { useUserStore } from "@/store/userStore";
import { getImageUrl } from "@/apis/productApi";
import { useToast } from "@/hooks/useToast";
import { useNavigate } from "react-router-dom";
import { createOrderFromCartApi } from "@/apis/orderApi";

function Cart() {
  const user = useUserStore((s) => s.user);
  const { toast, showToast } = useToast();

  const items = useCartStore((s) => s.items);
  const totalAmount = useCartStore((s) => s.totalAmount);
  const loading = useCartStore((s) => s.loading);
  const loadCart = useCartStore((s) => s.loadCart);
  const updateQuantity = useCartStore((s) => s.updateQuantity);
  const removeItem = useCartStore((s) => s.removeItem);
  const clearCart = useCartStore((s) => s.clearCart);

  const formatPrice = (price: number) => {
    return Number(price).toLocaleString("vi-VN") + " VND";
  };

  useEffect(() => {
    if (user?.user_id) {
      loadCart(user.user_id);
    }
  }, [user?.user_id]);

  const handleUpdateQuantity = async (cartItemId: number, quantity: number) => {
    if (!user?.user_id) return;

    const res = await updateQuantity(user.user_id, cartItemId, quantity);

    if (!res.success) {
      showToast(res.message || "Could not update quantity.", "error");
      return;
    }

    showToast("Cart updated.", "success");
  };

  const handleRemove = async (cartItemId: number) => {
    if (!user?.user_id) return;

    const res = await removeItem(user.user_id, cartItemId);

    if (!res.success) {
      showToast(res.message || "Could not remove this product.", "error");
      return;
    }

    showToast("Product removed from cart.", "success");
  };

  const handleClear = async () => {
    if (!user?.user_id) return;

    const res = await clearCart(user.user_id);

    if (!res.success) {
      showToast(res.message || "Could not clear the cart.", "error");
      return;
    }

    showToast("Cart cleared.", "success");
  };
  

  if (!user) {
    return (
      <div className="min-h-screen bg-slate-100 flex items-center justify-center">
        <div className="bg-white rounded-xl shadow p-8">
          Please log in to view your cart.
        </div>
      </div>
    );
  }

  const totalQuantity = items.reduce(
    (sum, item) => sum + Number(item.quantity),
    0
    );

    const navigate = useNavigate();
    const handleCheckout = async () => {
    if (!user?.user_id) {
        showToast("Please log in to checkout.", "error");
        return;
    }

    if (items.length === 0) {
        showToast("Your cart is empty.", "error");
        return;
    }

    try {
        const res = await createOrderFromCartApi({
        customer_id: user.user_id,
        payment_method: "cod",
        note: "Customer created order from cart",
        });

        if (!res.success) {
        showToast(res.message || "Could not create the order.", "error");
        return;
        }

        showToast("Order created successfully.", "success");

        await loadCart(user.user_id);

        setTimeout(() => {
        navigate("/orders");
        }, 600);
    } catch (err: any) {
        showToast(
        err.response?.data?.message || "Could not connect to the server.",
        "error"
        );
    }
    };

  return (
    <div className="min-h-screen bg-slate-100 px-4 py-8">
      <div className="max-w-6xl mx-auto">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-3xl font-bold">Cart</h1>

          {items.length > 0 && (
            <button
              onClick={handleClear}
              className="bg-red-600 text-white px-4 py-2 rounded-lg"
            >
              Clear Cart
            </button>
          )}
        </div>

        {loading ? (
          <div className="bg-white rounded-xl shadow p-8 text-center">
            Loading cart...
          </div>
        ) : items.length === 0 ? (
          <div className="bg-white rounded-xl shadow p-8 text-center">
            Your cart is empty.
          </div>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6">
            <div className="space-y-4">
              {items.map((item) => (
                <div
                  key={item.cart_item_id}
                  className="bg-white rounded-xl shadow p-4 grid grid-cols-[120px_1fr] gap-4"
                >
                  <img
                    src={getImageUrl(item.image_url)}
                    alt={item.product_name}
                    className="w-full h-36 object-cover rounded-lg bg-slate-200"
                    onError={(e) => {
                      e.currentTarget.src = "/icons.svg";
                    }}
                  />

                  <div>
                    <h2 className="text-xl font-bold">{item.product_name}</h2>

                    <p className="text-sm text-slate-600 mt-1">
                      SKU: {item.sku}
                    </p>

                    <p className="mt-1">
                      <b>Version:</b> {item.version_name}
                    </p>

                    <p className="mt-1">
                      <b>Price:</b> {formatPrice(item.price)}
                    </p>

                    <p className="mt-1">
                      <b>Subtotal:</b> {formatPrice(item.subtotal)}
                    </p>

                    <div className="flex items-center gap-2 mt-4">
                      <button
                        onClick={() =>
                          handleUpdateQuantity(
                            item.cart_item_id,
                            item.quantity - 1
                          )
                        }
                        className="px-3 py-1 border rounded"
                      >
                        -
                      </button>

                      <span className="px-4">{item.quantity}</span>

                      <button
                        onClick={() =>
                          handleUpdateQuantity(
                            item.cart_item_id,
                            item.quantity + 1
                          )
                        }
                        disabled={item.quantity >= item.stock_quantity}
                        className="px-3 py-1 border rounded disabled:opacity-50"
                      >
                        +
                      </button>

                      <button
                        onClick={() => handleRemove(item.cart_item_id)}
                        className="ml-4 bg-red-600 text-white px-4 py-1 rounded"
                      >
                        Remove
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            <div className="bg-white rounded-xl shadow p-6 h-fit">
              <h2 className="text-2xl font-bold mb-4">Order Summary</h2>


            <div className="flex justify-between mb-3">
                <span>Product types:</span>
                <b>{items.length}</b>
            </div>

            <div className="flex justify-between mb-3">
                <span>Total quantity:</span>
                <b>{totalQuantity}</b>
            </div>

              <div className="flex justify-between mb-6">
                <span>Total:</span>
                <b>{formatPrice(totalAmount)}</b>
              </div>

                <button
                    onClick={handleCheckout}
                    className="w-full bg-blue-600 text-white py-3 rounded-lg"
                    >
                    Checkout
                </button>
            </div>
          </div>
        )}
      </div>

      {toast.show && (
        <div className={`toast ${toast.type} show`}>{toast.message}</div>
      )}
    </div>
  );
}

export default Cart;
