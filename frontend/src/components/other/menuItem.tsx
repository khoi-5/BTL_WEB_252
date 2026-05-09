import { ChevronRightIcon, FileIcon } from "lucide-react"
import { Link } from "react-router-dom"

import { Button } from "@/components/ui/button"
import { Card, CardContent } from "@/components/ui/card"
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from "@/components/ui/collapsible"


type FileTreeItem = { name: string; to: string } | { name: string; items: FileTreeItem[] }

export function MenuItem() {
    const fileTree: FileTreeItem[] = [
        {
            name: "HOME",
            items: [
                { name: "Storefront", to: "/" },
                { name: "Browse Products", to: "/products" },
            ],
        },
        {
            name: "ABOUT US",
            items: [
                { name: "Our Story", to: "/about" },
                { name: "Contact Support", to: "/contact" },
            ],
        },
        {
            name: "PRODUCTS",
            items: [
                { name: "All Products", to: "/products" },
                { name: "Flash Sales", to: "/products" },
                { name: "Collections", to: "/products" },
                { name: "Cart", to: "/cart" },
            ],
        },
        {
            name: "FAQ",
            items: [{ name: "FAQ Center", to: "/faq" }],
        },
        {
            name: "CONTACT",
            items: [{ name: "Send Message", to: "/contact" }],
        },
    ]

    const renderItem = (fileItem: FileTreeItem) => {
        if ("items" in fileItem) {
            return (
                <Collapsible key={fileItem.name}>
                    <CollapsibleTrigger asChild>
                        <Button
                            variant="ghost"
                            size="sm"
                            className="group  w-full h-12 justify-start text-xl transition-none hover:bg-(--color-hover) hover:text-accent-foreground"
                        >
                            <ChevronRightIcon className="transition-transform group-data-[state=open]:rotate-90" />
                            {/* <FolderIcon /> */}
                            {fileItem.name}
                        </Button>
                    </CollapsibleTrigger>
                    <CollapsibleContent className="mt-1 ml-5 style-lyra:ml-4">
                        <div className="flex flex-col gap-1">
                            {fileItem.items.map((child) => renderItem(child))}
                        </div>
                    </CollapsibleContent>
                </Collapsible>
            )
        }
        return (
            <Button
                key={fileItem.name}
                asChild
                variant="link"
                size="sm"
                className="w-full justify-start gap-2 text-foreground"
            >
                <Link to={fileItem.to}>
                    <FileIcon />
                    <span>{fileItem.name}</span>
                </Link>
            </Button>
        )
    }

    return (
        <Card className="mx-4 w-auto gap-2" size="sm">
            {/* <CardHeader>
        <div></div>
      </CardHeader> */}
            <CardContent>
                <div className="flex flex-col gap-1">
                    {fileTree.map((item) => renderItem(item))}
                </div>
            </CardContent>
        </Card>
    )
}
