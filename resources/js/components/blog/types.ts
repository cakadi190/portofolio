export type BlogCommentNode = {
  id: number;
  body: string;
  author: string;
  createdAt: string;
  replies: BlogCommentNode[];
};

export type BlogCategoryRef = {
  name: string;
  slug: string;
  color: string | null;
};
export type BlogTagRef = { name: string; slug: string };
export type BlogAuthorRef = { name: string; slug: string | null };

/** Public card payload of a post, as built by `PresentsBlogPosts::postCard()`. */
export type BlogPostCard = {
  title: string;
  slug: string;
  excerpt: string | null;
  coverImage: string | null;
  publishedAt: string | null;
  author: BlogAuthorRef | null;
  categories: BlogCategoryRef[];
  tags: BlogTagRef[];
  /** Only present for staff who may edit the post. */
  editUrl: string | null;
};
