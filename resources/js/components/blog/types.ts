export type BlogCommentNode = {
  id: number;
  body: string;
  author: string;
  createdAt: string;
  replies: BlogCommentNode[];
};
