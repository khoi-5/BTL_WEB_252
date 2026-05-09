import * as z from "zod";

export const UserData = z.object({
  user_id: z.number(),
  full_name: z.string(),
  email: z.string().email(),
  phone: z.string().nullable().optional(),
  avatar: z.string().nullable().optional(),

  role: z.enum(["admin", "customer"]),

  customer_status: z.number().optional(),
  is_super_admin: z.number().optional(),
});

export type UserDataType = z.infer<typeof UserData>;
