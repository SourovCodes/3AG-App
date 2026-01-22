export type * from './auth';
export type * from './dashboard';
export type * from './flash';
export type * from './pagination';
export type * from './product';

import type { Auth } from './auth';

export interface SharedData {
    name: string;
    auth: Auth | null;
    [key: string]: unknown;
}
