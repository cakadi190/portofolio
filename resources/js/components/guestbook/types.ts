export type GuestbookNode = {
  id: number;
  body: string;
  author: string;
  /** Storage path of the account's avatar; null for guests and users without one. */
  authorAvatar: string | null;
  createdAt: string;
  replies: GuestbookNode[];
};
