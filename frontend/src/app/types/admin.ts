export type PublicationStatus = "draft" | "pending_review" | "published";

export interface StoryGoal {
  id: number;
  name: string;
  description: string | null;
  book_template?: BookTemplate | null;
}

export interface BookTemplate {
  id: number;
  title: string;
  /** Borrowed from linked StoryGoal; not stored on the template. */
  description: string | null;
  is_free: boolean;
  template_type: string;
  is_active: boolean;
  publication_status: PublicationStatus;
  version: number;
  story_goal_id: number | null;
  story_goal?: { id: number; name: string; description?: string | null } | null;
}

export interface StoryPrompt {
  id: number;
  title: string;
  prompt_text: string;
  language: string;
  age_range: string | null;
  story_goal_id: number | null;
  quality_score: number;
  rating_count: number;
  usage_count: number;
  is_active: boolean;
  publication_status: PublicationStatus;
  version: number;
  story_goal?: { id: number; name: string } | null;
}

export interface LayoutTemplate {
  id: number;
  key: string;
  title: string;
  category: "cover" | "content" | "ending";
  ratio_profile: string;
  text_position: string;
  sort_order: number;
  is_active: boolean;
  publication_status: PublicationStatus;
  version: number;
}

export interface ReviewQueueItem {
  type: "book_template" | "story_prompt" | "layout_template";
  id: number;
  title: string;
  updated_at: string | null;
}

export interface PreviewPage {
  page_number: number;
  text: string;
  image_url: string | null;
  layout_template: {
    id?: number;
    key: string;
    title: string;
    category: string;
    text_position: string;
    ratio_profile: string;
  };
}

export interface ContentPreview {
  type: string;
  title: string;
  description?: string;
  prompt_text?: string;
  pages: PreviewPage[];
}
