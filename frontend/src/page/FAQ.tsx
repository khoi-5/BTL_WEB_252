import { useEffect, useState } from "react";
import { getFaqsApi, getFaqCategoriesApi, type FaqItem } from "@/apis/faqApi";

function FAQ() {
  const [faqs, setFaqs] = useState<FaqItem[]>([]);
  const [categories, setCategories] = useState<string[]>([]);
  const [activeCategory, setActiveCategory] = useState("");
  const [openId, setOpenId] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getFaqCategoriesApi().then((res) => {
      if (res.success) setCategories(res.data);
    });
  }, []);

  useEffect(() => {
    setLoading(true);
    getFaqsApi(activeCategory || undefined)
      .then((res) => {
        if (res.success) setFaqs(res.data);
      })
      .finally(() => setLoading(false));
  }, [activeCategory]);

  return (
    <div style={{ maxWidth: 700, margin: "40px auto", padding: "0 20px" }}>
      <h1 style={{ marginBottom: 8 }}>Frequently Asked Questions</h1>
      <p style={{ color: "#666", marginBottom: 24 }}>
        Find answers to the most common questions.
      </p>

      {/* Category filter */}
      {categories.length > 0 && (
        <div style={{ display: "flex", gap: 8, flexWrap: "wrap", marginBottom: 24 }}>
          <button
            onClick={() => setActiveCategory("")}
            style={{
              padding: "6px 16px", borderRadius: 20, border: "1px solid #ccc", cursor: "pointer",
              background: activeCategory === "" ? "#2563eb" : "#fff",
              color: activeCategory === "" ? "#fff" : "#333",
            }}
          >
            All
          </button>
          {categories.map((cat) => (
            <button
              key={cat} onClick={() => setActiveCategory(cat)}
              style={{
                padding: "6px 16px", borderRadius: 20, border: "1px solid #ccc", cursor: "pointer",
                background: activeCategory === cat ? "#2563eb" : "#fff",
                color: activeCategory === cat ? "#fff" : "#333",
              }}
            >
              {cat}
            </button>
          ))}
        </div>
      )}

      {/* FAQ list */}
      {loading ? (
        <p>Loading...</p>
      ) : faqs.length === 0 ? (
        <p style={{ color: "#999" }}>No questions yet.</p>
      ) : (
        <div>
          {faqs.map((faq) => (
            <div
              key={faq.faq_id}
              style={{
                border: "1px solid #e5e7eb", borderRadius: 8, marginBottom: 8,
                overflow: "hidden",
              }}
            >
              <button
                onClick={() => setOpenId(openId === faq.faq_id ? null : faq.faq_id)}
                style={{
                  width: "100%", padding: "14px 16px", background: "#f9fafb",
                  border: "none", textAlign: "left", cursor: "pointer",
                  fontWeight: 600, fontSize: 15, display: "flex",
                  justifyContent: "space-between", alignItems: "center",
                }}
              >
                <span>{faq.question}</span>
                <span style={{ fontSize: 18 }}>{openId === faq.faq_id ? "−" : "+"}</span>
              </button>
              {openId === faq.faq_id && (
                <div style={{ padding: "12px 16px", background: "#fff", color: "#555", lineHeight: 1.6 }}>
                  {faq.answer}
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

export default FAQ;
