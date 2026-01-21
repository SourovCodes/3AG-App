import { Link, usePage } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import type { SharedData } from '@/types';

export function Navbar() {
    const { auth } = usePage<SharedData>().props;

    return (
        <header className="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div className="container mx-auto flex h-16 items-center justify-between px-4">
                <Link href="/" className="flex items-center gap-2">
                    <svg className="h-8 w-8 text-primary" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="32" height="32" rx="8" fill="currentColor" />
                        <path d="M8 16L14 22L24 10" stroke="white" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                    <span className="text-xl font-bold">3AG</span>
                </Link>

                <nav className="hidden items-center gap-6 md:flex">
                    <Link href="/" className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">
                        Home
                    </Link>
                    <Link href="#features" className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">
                        Features
                    </Link>
                    <Link href="#pricing" className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground">
                        Pricing
                    </Link>
                </nav>

                <div className="flex items-center gap-4">
                    {auth?.user ? (
                        <>
                            <span className="text-sm text-muted-foreground">Hi, {auth.user.name}</span>
                            <Link href="/logout" method="post" as="button">
                                <Button variant="outline" size="sm">
                                    Logout
                                </Button>
                            </Link>
                        </>
                    ) : (
                        <>
                            <Link href="/login">
                                <Button variant="ghost" size="sm">
                                    Login
                                </Button>
                            </Link>
                            <Link href="/register">
                                <Button size="sm">Get Started</Button>
                            </Link>
                        </>
                    )}
                </div>
            </div>
        </header>
    );
}
