export type User = {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    is_admin: boolean;
};

export type Auth = {
    user: User | null;
};
