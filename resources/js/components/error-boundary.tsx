import { Component } from 'react';
import type { ErrorInfo, ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import FuzzyText from  './FuzzyText';

interface Props {
    children: ReactNode;
}

interface State {
    hasError: boolean;
    error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
    constructor(props: Props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, errorInfo: ErrorInfo) {
        console.error('ErrorBoundary caught:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="flex min-h-[60vh] flex-col items-center justify-center px-6 text-center">
                    <div className="mx-auto max-w-md space-y-6">
                        <div className="space-y-2">
                            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                <svg
                                    className="h-8 w-8 text-red-600 dark:text-red-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    strokeWidth="1.5"
                                    stroke="currentColor"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
                                    />
                                </svg>
                            </div>

                            <FuzzyText 
                                baseIntensity={0.2}
                                hoverIntensity={0.5}
                                enableHover
                            >
                                Något gick fel
                            </FuzzyText>

                            <p className="text-sm text-muted-foreground">
                                Ett oväntat fel inträffade. Det är oftast
                                tillfälligt, försök att ladda om sidan.
                            </p>
                        </div>

                        {this.state.error && (
                            <div className="rounded-lg border bg-muted/50 p-4 text-left">
                                <p className="mb-1 text-xs font-medium tracking-wider text-muted-foreground uppercase">
                                    Feldetaljer
                                </p>
                                <p className="font-mono text-xs text-red-600 dark:text-red-400">
                                    {this.state.error.message}
                                </p>
                            </div>
                        )}

                        <div className="flex justify-center gap-3">
                            <Button
                                variant="outline"
                                onClick={() =>
                                    this.setState({
                                        hasError: false,
                                        error: null,
                                    })
                                }
                            >
                                Försök igen
                            </Button>

                            <Button onClick={() => window.location.reload()}>
                                Ladda om sidan
                            </Button>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}
