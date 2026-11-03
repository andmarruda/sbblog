export type BlogSummary = {
    id: string;
    name: string;
    slug: string;
};

export type WorkspaceSummary = {
    id: string;
    name: string;
    slug: string;
    role: 'owner' | 'admin' | 'member';
    blogs: BlogSummary[];
};

export type CurrentWorkspace = Omit<WorkspaceSummary, 'blogs'>;
