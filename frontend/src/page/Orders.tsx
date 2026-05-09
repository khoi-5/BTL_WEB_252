import { useEffect, useState } from "react";
import { useUserStore } from "@/store/userStore";
import { useToast } from "@/hooks/useToast";
import type { Order, OrderStatus } from "@/schema/order";
import {
  customerCancelOrderApi,
  customerConfirmDeliveredApi,
  customerConfirmPaymentApi,
  getCustomerOrdersApi,
} from "@/apis/orderApi";

function Orders() {
  const user = useUserStore((s) => s.user);
  const { toast, showToast } = useToast();

  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(false);

  const formatPrice = (price: number) => {
    return Number(price).toLocaleString("vi-VN") + " VND";
  };

  const statusText: Record<OrderStatus, string> = {
    pending: "Awaiting Payment",
    confirmed: "Payment Confirmed",
    shipping: "Shipping",
    delivered: "Delivered",
    cancelled: "Cancelled",
  };

  const statusClass: Record<OrderStatus, string> = {
    pending: "bg-yellow-100 text-yellow-700",
    confirmed: "bg-blue-100 text-blue-700",
    shipping: "bg-purple-100 text-purple-700",
    delivered: "bg-green-100 text-green-700",
    cancelled: "bg-red-100 text-red-700",
  };

  const loadOrders = async () => {
    if (!user?.user_id) return;

    try {
      setLoading(true);

      const res = await getCustomerOrdersApi(user.user_id);

      if (!res.success) {
        showToast(res.message || "Could not load orders.", "error");
        return;
      }

      setOrders(res.data || []);
    } catch (err: any) {
      showToast(
        err.response?.data?.message || "Could not connect to the server.",
        "error"
      );
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadOrders();
  }, [user?.user_id]);

  const handleConfirmPayment = async (orderId: number) => {
    if (!user?.user_id) return;

    const res = await customerConfirmPaymentApi(orderId, user.user_id);

    if (!res.success) {
      showToast(res.message || "Could not confirm payment.", "error");
      return;
    }

    showToast("Payment confirmed.", "success");
    loadOrders();
  };

  const handleCancelOrder = async (orderId: number) => {
    if (!user?.user_id) return;

    const ok = window.confirm("Are you sure you want to cancel this order?");
    if (!ok) return;

    const res = await customerCancelOrderApi(orderId, user.user_id);

    if (!res.success) {
      showToast(res.message || "Could not cancel this order.", "error");
      return;
    }

    showToast("Order cancelled.", "success");
    loadOrders();
  };

  const handleConfirmDelivered = async (orderId: number) => {
    if (!user?.user_id) return;

    const res = await customerConfirmDeliveredApi(orderId, user.user_id);

    if (!res.success) {
      showToast(res.message || "Could not confirm delivery.", "error");
      return;
    }

    showToast("Delivery confirmed.", "success");
    loadOrders();
  };

  if (!user) {
    return (
      <div className="min-h-screen bg-slate-100 flex items-center justify-center">
        <div className="bg-white rounded-xl shadow p-8">
          Please log in to view your orders.
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-100 px-4 py-8">
      <div className="max-w-6xl mx-auto">
        <h1 className="text-3xl font-bold mb-6">My Orders</h1>

        {loading ? (
          <div className="bg-white rounded-xl shadow p-8 text-center">
            Loading orders...
          </div>
        ) : orders.length === 0 ? (
          <div className="bg-white rounded-xl shadow p-8 text-center">
            You do not have any orders yet.
          </div>
        ) : (
          <div className="space-y-5">
            {orders.map((order) => (
              <div key={order.order_id} className="bg-white rounded-xl shadow">
                <div className="p-5 border-b flex justify-between gap-4">
                  <div>
                    <h2 className="text-xl font-bold">
                      Order #{order.order_id}
                    </h2>
                    <p className="text-slate-600">
                      Order date: {order.order_date}
                    </p>
                  </div>

                  <span
                    className={`h-fit px-4 py-2 rounded-full text-sm font-semibold ${
                      statusClass[order.order_status]
                    }`}
                  >
                    {statusText[order.order_status]}
                  </span>
                </div>

                <div className="p-5">
                  <div className="mb-4">
                    <p>
                      <b>Receiver:</b> {order.receiver_name}
                    </p>
                    <p>
                      <b>Phone:</b> {order.receiver_phone}
                    </p>
                    <p>
                      <b>Address:</b> {order.shipping_address}
                    </p>
                  </div>

                  <div className="border rounded-lg overflow-hidden">
                    <table className="w-full text-left">
                      <thead className="bg-slate-100">
                        <tr>
                          <th className="p-3">Product</th>
                          <th className="p-3">Version</th>
                          <th className="p-3">Price</th>
                          <th className="p-3">SL</th>
                          <th className="p-3">Subtotal</th>
                        </tr>
                      </thead>

                      <tbody>
                        {(order.items || []).map((item) => (
                          <tr key={item.order_item_id} className="border-t">
                            <td className="p-3">
                              {item.product_name_snapshot}
                            </td>
                            <td className="p-3">
                              {item.version_name_snapshot}
                            </td>
                            <td className="p-3">
                              {formatPrice(item.unit_price)}
                            </td>
                            <td className="p-3">{item.quantity}</td>
                            <td className="p-3">
                              {formatPrice(item.subtotal)}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  <div className="mt-5 flex justify-between items-center">
                    <div className="text-xl font-bold">
                      Total: {formatPrice(order.total_amount)}
                    </div>

                    <div className="flex gap-2">
                      {order.order_status === "pending" && (
                        <>
                          <button
                            onClick={() =>
                              handleConfirmPayment(order.order_id)
                            }
                            className="bg-blue-600 text-white px-4 py-2 rounded-lg"
                          >
                            Confirm Payment
                          </button>

                          <button
                            onClick={() => handleCancelOrder(order.order_id)}
                            className="bg-red-600 text-white px-4 py-2 rounded-lg"
                          >
                            Cancel Order
                          </button>
                        </>
                      )}

                      {order.order_status === "shipping" && (
                        <button
                          onClick={() =>
                            handleConfirmDelivered(order.order_id)
                          }
                          className="bg-green-600 text-white px-4 py-2 rounded-lg"
                        >
                          Mark as Delivered
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {toast.show && (
        <div className={`toast ${toast.type} show`}>{toast.message}</div>
      )}
    </div>
  );
}

export default Orders;
