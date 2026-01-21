export type * from './auth';
export type * from './flash';

import type { Auth } from './auth';

export interface SharedData {
    name: string;
    auth: Auth | null;
    [key: string]: unknown;
}
