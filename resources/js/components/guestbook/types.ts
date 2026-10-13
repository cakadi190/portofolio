export type GuestbookNode = {
  id: number;
  body: string;
  author: string;
  createdAt: string;
  replies: GuestbookNode[];
};
