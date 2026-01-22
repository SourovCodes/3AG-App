import { Head, Link } from '@inertiajs/react';

import { show } from '@/actions/App/Http/Controllers/ProductController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { CustomPagination } from '@/components/ui/custom-pagination';
import type { PaginatedData, Product } from '@/types';

interface Props {
    products: PaginatedData<Product>;
}

const typeColors: Record<Product['type'], 'default' | 'secondary' | 'outline'> = {
    plugin: 'default',
    theme: 'secondary',
    source_code: 'outline',
};

export default function ProductsIndex({ products }: Props) {
    return (
        <>
            <Head title="Products" />

            <div className="container mx-auto px-4 py-12">
                <div className="mb-8 text-center">
                    <h1 className="mb-4 text-4xl font-bold">Our Products</h1>
                    <p className="mx-auto max-w-2xl text-muted-foreground">
                        Explore our collection of premium plugins, themes, and source code packages built for developers.
                    </p>
                </div>

                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {products.data.map((product) => (
                        <Card key={product.id} className="flex flex-col">
                            <CardHeader className="flex-1">
                                <div className="mb-2 flex items-center justify-between">
                                    <Badge variant={typeColors[product.type]}>{product.type_label}</Badge>
                                    <span className="text-sm text-muted-foreground">v{product.version}</span>
                                </div>
                                <CardTitle className="text-xl">{product.name}</CardTitle>
                                <CardDescription className="line-clamp-3">{product.description}</CardDescription>
                            </CardHeader>
                            <CardFooter>
                                <Link href={show.url({ product: product.slug })} className="w-full">
                                    <Button variant="outline" className="w-full">
                                        View Details
                                    </Button>
                                </Link>
                            </CardFooter>
                        </Card>
                    ))}
                </div>

                {products.data.length === 0 && (
                    <div className="py-12 text-center">
                        <p className="text-muted-foreground">No products available at the moment.</p>
                    </div>
                )}

                <div className="mt-8">
                    <CustomPagination currentPage={products.current_page} totalPages={products.last_page} />
                </div>
            </div>
        </>
    );
}
