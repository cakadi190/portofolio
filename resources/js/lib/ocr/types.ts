export type Rect = [number, number, number, number];
export type OcrWord = { text: string; rect: Rect };
export type OcrLine = OcrWord[];
export type OcrMatch = { rect: Rect; snippet: string };
