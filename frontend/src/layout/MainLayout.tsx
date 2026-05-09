import { Outlet } from 'react-router-dom'
import { SidebarProvider, SidebarTrigger, SidebarInset } from "@/components/ui/sidebar"

import { Navbar } from './Navbar'
import Header from './Header'
import Footer from './Footer'
import SidebarMenu from './Sidebar'

function MainLayout() {
    return (

        <SidebarProvider>
            {/* 1. Sidebar nằm bên trái */}
            <SidebarMenu />

            {/* 2. Phần nội dung chính nằm bên phải */}
            <SidebarInset className="flex flex-col w-full pt-4">
                <div className='sticky bg-white top-4 z-10 pb-4 shadow-md'>
                    <header className="flex h-16 shrink-0 items-center">

                        <Header leftTrigger={<SidebarTrigger />} />
                    </header>

                    <Navbar />
                </div>

                {/* Nội dung thay đổi theo Route */}
                <main className="flex-1">
                    <Outlet />
                </main>

                <Footer />
            </SidebarInset>
        </SidebarProvider>
    )
}

export default MainLayout
