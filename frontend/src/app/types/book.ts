export interface LayoutTemplate {
  id: number;
  key: string;
  category: string;
  ratio_profile: string;
  text_position: "top" | "bottom" | "left" | "right";
}

export interface BookPage {
  id: number;
  page_number: number;
  text: string;
  image_url: string | null;
  layout_template: LayoutTemplate | null;
}

export interface BookTemplateSummary {
  id: number;
  title: string;
}

export interface BookGeneration {
  id: number;
  child_name: string;
  child_age: number;
  child_goal: string;
  status: string;
  illustration_status?: string | null;
  error_message?: string | null;
  created_at: string;
  book_template?: BookTemplateSummary | null;
  book_pages: BookPage[];
}
