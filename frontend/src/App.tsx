import { Route, createRoutesFromElements, RouterProvider, createBrowserRouter } from 'react-router-dom';

import './App.css'
import MainLayout from './layout/MainLayout';

import Home from './page/Home';
import Products from './page/Products';
import ProductDetails from './page/ProductDetails';
import Contact from './page/Contact';
import AboutUs from './page/AboutUs';
import FAQ from './page/FAQ';

// đăng nhập và đăng ký
import Login from "@/page/Login";
import AdminLogin from "@/page/AdminLogin";

import Register from "@/page/Register";

// thông tin khách hàng
import Profile from "@/page/Profile";

//cart
import Cart from "@/page/Cart";

//order
import Orders from "@/page/Orders";

const routes = createRoutesFromElements(

  <>

    <Route path="/" element={<MainLayout />}>

      <Route index element={<Home />} />
      <Route path="products" element={<Products />} />
      <Route path="products/:id" element={<ProductDetails />} />

      <Route path="about" element={<AboutUs />} />
      <Route path="contact" element={<Contact />} />
      <Route path="faq" element={<FAQ />} />

      {/* đăng nhập và đăng ký */}
      <Route path="/login" element={<Login />} />
      <Route path="/admin/login" element={<AdminLogin />} />

      <Route path="/register" element={<Register />} />

      {/* thông tin khách hàng */}
      <Route path="/profile" element={<Profile />} />
      
      {/* cart */}
      <Route path="/cart" element={<Cart />} />

      {/* order */}
      <Route path="/orders" element={<Orders />} />

    </Route>



  </>

);

const router = createBrowserRouter(routes)

function App() {


  return <RouterProvider router={router} />;
}

export default App
