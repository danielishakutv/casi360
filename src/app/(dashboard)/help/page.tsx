"use client";

import * as React from "react";
import {
  Search,
  HelpCircle,
  BookOpen,
  MessageCircle,
  Shield,
  Users,
  Settings,
  Database,
  ChevronDown,
} from "lucide-react";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import { Badge } from "@/components/ui/badge";
import { mockFAQs } from "@/mock/faqs";

const categories = [
  {
    name: "HR",
    icon: Users,
    color:
      "text-blue-600 bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400",
    count: 0,
  },
  {
    name: "Account",
    icon: Shield,
    color:
      "text-purple-600 bg-purple-100 dark:bg-purple-900/30 dark:text-purple-400",
    count: 0,
  },
  {
    name: "Communication",
    icon: MessageCircle,
    color:
      "text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400",
    count: 0,
  },
  {
    name: "Settings",
    icon: Settings,
    color:
      "text-amber-600 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400",
    count: 0,
  },
  {
    name: "Approvals",
    icon: BookOpen,
    color:
      "text-rose-600 bg-rose-100 dark:bg-rose-900/30 dark:text-rose-400",
    count: 0,
  },
  {
    name: "Technical",
    icon: Database,
    color:
      "text-cyan-600 bg-cyan-100 dark:bg-cyan-900/30 dark:text-cyan-400",
    count: 0,
  },
];

export default function HelpPage() {
  const [search, setSearch] = React.useState("");
  const [selectedCategory, setSelectedCategory] = React.useState<string | null>(
    null
  );

  // Count FAQs per category
  const categoriesWithCounts = categories.map((cat) => ({
    ...cat,
    count: mockFAQs.filter((f) => f.category === cat.name).length,
  }));

  const filteredFAQs = mockFAQs.filter((faq) => {
    const matchSearch =
      search === "" ||
      faq.question.toLowerCase().includes(search.toLowerCase()) ||
      faq.answer.toLowerCase().includes(search.toLowerCase());
    const matchCategory =
      !selectedCategory || faq.category === selectedCategory;
    return matchSearch && matchCategory;
  });

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="text-center py-8">
        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10">
          <HelpCircle className="h-8 w-8 text-primary" />
        </div>
        <h1 className="text-3xl font-bold tracking-tight">Help Center</h1>
        <p className="mt-2 text-muted-foreground max-w-md mx-auto">
          Find answers to common questions and learn how to use CASI360
          effectively.
        </p>

        {/* Search */}
        <div className="mt-6 max-w-lg mx-auto relative">
          <Search className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-muted-foreground" />
          <Input
            placeholder="Search for help..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="h-12 pl-12 text-base rounded-xl"
          />
        </div>
      </div>

      {/* Categories */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {categoriesWithCounts.map((cat) => (
          <Card
            key={cat.name}
            className={`cursor-pointer transition-all hover:shadow-md ${
              selectedCategory === cat.name
                ? "ring-2 ring-primary"
                : ""
            }`}
            onClick={() =>
              setSelectedCategory(
                selectedCategory === cat.name ? null : cat.name
              )
            }
          >
            <CardContent className="p-4 flex items-center gap-4">
              <div
                className={`flex h-12 w-12 items-center justify-center rounded-xl ${cat.color}`}
              >
                <cat.icon className="h-6 w-6" />
              </div>
              <div>
                <p className="font-medium">{cat.name}</p>
                <p className="text-xs text-muted-foreground">
                  {cat.count} articles
                </p>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* FAQs */}
      <Card>
        <CardHeader>
          <CardTitle className="text-base">
            Frequently Asked Questions
            {selectedCategory && (
              <Badge
                variant="secondary"
                className="ml-2 cursor-pointer"
                onClick={() => setSelectedCategory(null)}
              >
                {selectedCategory} ×
              </Badge>
            )}
          </CardTitle>
          <CardDescription>
            {filteredFAQs.length} question
            {filteredFAQs.length !== 1 ? "s" : ""} found
          </CardDescription>
        </CardHeader>
        <CardContent>
          {filteredFAQs.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
              <HelpCircle className="mb-3 h-10 w-10" />
              <p className="text-sm">No questions found</p>
              <p className="text-xs">Try adjusting your search or category</p>
            </div>
          ) : (
            <Accordion type="single" collapsible className="w-full">
              {filteredFAQs.map((faq) => (
                <AccordionItem key={faq.id} value={faq.id}>
                  <AccordionTrigger className="text-left text-sm hover:no-underline">
                    <div className="flex items-center gap-3">
                      <Badge variant="outline" className="text-[10px] shrink-0">
                        {faq.category}
                      </Badge>
                      <span>{faq.question}</span>
                    </div>
                  </AccordionTrigger>
                  <AccordionContent className="text-sm text-muted-foreground leading-relaxed pl-[72px]">
                    {faq.answer}
                  </AccordionContent>
                </AccordionItem>
              ))}
            </Accordion>
          )}
        </CardContent>
      </Card>

      {/* Contact Support */}
      <Card className="border-dashed">
        <CardContent className="p-6 text-center">
          <MessageCircle className="mx-auto mb-3 h-8 w-8 text-muted-foreground" />
          <h3 className="font-medium">Still need help?</h3>
          <p className="mt-1 text-sm text-muted-foreground">
            Contact our support team at{" "}
            <span className="font-medium text-foreground">
              support@casi.org
            </span>
          </p>
          <p className="text-xs text-muted-foreground mt-1">
            We typically respond within 24 hours during business days.
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
